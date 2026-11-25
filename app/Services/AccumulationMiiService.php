<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Formulaire KoboToolbox « ENDURENET – Accumulation, stockage et utilisation des MII »
 * (suivi 6 mois, un entretien par ménage). Population cible : ménages consentis qui
 * disposaient d'anciennes moustiquaires à la baseline (liste menages.csv jointe au formulaire).
 */
class AccumulationMiiService
{
    public const COMPUTED_KEY = 'kobo.accum.computed';
    private const CACHE_KEYS  = ['kobo.accum.asset', 'kobo.accum.subs', 'kobo.accum.csv.menages.csv', 'kobo.accum.csv.agents_terrain.csv'];

    public const STATUS_LABELS = [
        'realise'     => 'Entretien réalisé',
        'refus'       => 'Refus',
        'absent'      => 'Absent / pas de répondant',
        'introuvable' => 'Introuvable / déménagé',
        'non_visite'  => 'Non visité',
    ];
    public const STATUS_COLORS = [
        'realise' => '#16A34A', 'refus' => '#DC2626', 'absent' => '#D97706', 'introuvable' => '#7C3AED', 'non_visite' => '#94A3B8',
    ];

    /** Groupes analysés dans l'onglet Résultats (ordre d'affichage) */
    public const SECTIONS = [
        'section_A'   => 'A. Sélection et utilisation des moustiquaires',
        'section_B'   => 'B. Stockage des moustiquaires non utilisées',
        'observation' => 'Observation directe et photos',
        'section_C'   => 'C. Connaissances sur l\'efficacité et la durée de vie',
        'section_D'   => 'D. Projets futurs pour les moustiquaires non utilisées',
        'section_E'   => 'E. Raisons de l\'accumulation',
    ];

    public const QUERY_CODES = [
        'Q-ACC-01' => ['critical', 'Ménage hors de la liste cible'],
        'Q-ACC-02' => ['warning',  'Plusieurs entretiens réalisés pour le même ménage'],
        'Q-ACC-03' => ['critical', 'Date de visite incohérente'],
        'Q-ACC-04' => ['critical', 'Refus sans raison'],
        'Q-ACC-05' => ['warning',  'Photo déclarée prise mais fichier absent'],
        'Q-ACC-06' => ['critical', 'Entretien consenti sans réponses'],
        'Q-ACC-07' => ['info',     'Réponse ouverte suspecte (texte de test ?)'],
        'Q-ACC-08' => ['info',     'Ménage visité 3 fois ou plus sans entretien'],
        'Q-ACC-09' => ['warning',  'Ménage introuvable ou déménagé'],
        'Q-ACC-10' => ['warning',  'Village / grappe saisis ≠ liste des ménages'],
        'Q-ACC-11' => ['info',     'Saisie tardive (plus de 7 jours après la visite)'],
    ];

    private string $apiUrl;
    private string $token;
    private string $uid;

    public function __construct(private RedCapService $redcap)
    {
        $this->apiUrl = rtrim(config('services.kobotoolbox.api_url', 'https://kf.kobotoolbox.org/api/v2'), '/');
        $this->token  = (string) config('services.kobotoolbox.token');
        $this->uid    = (string) config('services.kobotoolbox.accum_form_uid');
        if ($this->token === '' || $this->uid === '') {
            throw new \RuntimeException('KoboToolbox non configuré : définissez KOBO_API_TOKEN et KOBO_ACCUM_FORM_UID dans le fichier .env');
        }
    }

    // ── Accès API ───────────────────────────────────────────────────────────

    private function http()
    {
        return Http::withHeaders(['Authorization' => "Token {$this->token}"])->timeout(90)->retry(2, 2000);
    }

    private function get(string $endpoint, array $params = []): array
    {
        $res = $this->http()->get("{$this->apiUrl}/{$endpoint}", $params);
        $res->throw();
        return $res->json() ?? [];
    }

    /** Définition du formulaire (questions, choix, fichiers) */
    public function asset(): array
    {
        return Cache::remember('kobo.accum.asset', now()->addHours(6), fn() => $this->get("assets/{$this->uid}/", ['format' => 'json']));
    }

    /** Toutes les soumissions, clés aplaties (« section_A/A1 » → « A1 ») */
    public function submissions(): array
    {
        return Cache::remember('kobo.accum.subs', now()->addMinutes(15), function () {
            $all = [];
            $start = 0;
            $limit = 1000;
            do {
                $page = $this->get("assets/{$this->uid}/data/", ['format' => 'json', 'limit' => $limit, 'start' => $start]);
                foreach ($page['results'] ?? [] as $s) {
                    $flat = [];
                    foreach ($s as $k => $v) {
                        $short = str_starts_with($k, '_') ? $k : (str_contains($k, '/') ? substr($k, strrpos($k, '/') + 1) : $k);
                        $flat[$short] = $v;
                    }
                    $all[] = $flat;
                }
                $start += $limit;
            } while (!empty($page['next']));
            return $all;
        });
    }

    /** Fichier CSV joint au formulaire (menages.csv, agents_terrain.csv) */
    private function mediaCsv(string $filename): array
    {
        return Cache::remember("kobo.accum.csv.$filename", now()->addHours(6), function () use ($filename) {
            $files = $this->get("assets/{$this->uid}/files/", ['format' => 'json']);
            $file  = collect($files['results'] ?? [])->first(fn($f) => ($f['metadata']['filename'] ?? '') === $filename);
            if (!$file) return [];
            $res = $this->http()->withOptions(['allow_redirects' => true])->get($file['content']);
            $res->throw();
            $lines = preg_split('/\r\n|\n|\r/', trim(preg_replace('/^\xEF\xBB\xBF/', '', $res->body())));
            $hdr   = str_getcsv(array_shift($lines));
            $rows  = [];
            foreach ($lines as $l) {
                if (trim($l) === '') continue;
                $vals = str_getcsv($l);
                $rows[] = array_combine($hdr, array_pad(array_slice($vals, 0, count($hdr)), count($hdr), ''));
            }
            return $rows;
        });
    }

    public function clearCache(): void
    {
        foreach (self::CACHE_KEYS as $k) Cache::forget($k);
        Cache::store('file')->forget(self::COMPUTED_KEY);
    }

    /** Contenu d'une photo jointe : [binaire, type MIME] ou null */
    public function photo(int $submissionId, string $field): ?array
    {
        $sub = collect($this->submissions())->first(fn($s) => (int) ($s['_id'] ?? 0) === $submissionId);
        if (!$sub) return null;
        $att = self::attachmentFor($sub, $field);
        if (!$att) return null;
        $url = $att['download_large_url'] ?? $att['download_url'] ?? null;
        if (!$url) return null;
        $res = $this->http()->withOptions(['allow_redirects' => true])->get($url);
        if (!$res->ok()) return null;
        return [$res->body(), $att['mimetype'] ?? ($res->header('Content-Type') ?: 'image/jpeg')];
    }

    private static function attachmentFor(array $sub, string $field): ?array
    {
        $value = (string) ($sub[$field] ?? '');
        foreach ($sub['_attachments'] ?? [] as $a) {
            if (isset($a['is_deleted']) && $a['is_deleted']) continue;
            $xpath = (string) ($a['question_xpath'] ?? '');
            $base  = basename((string) ($a['filename'] ?? ''));
            if ($xpath === $field || str_ends_with($xpath, "/$field")
                || ($value !== '' && ($base === $value || str_replace(' ', '_', $value) === $base))) {
                return $a;
            }
        }
        return null;
    }

    // ── Métadonnées du formulaire ───────────────────────────────────────────

    private static function clean(?string $label): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace('**', '', strip_tags((string) $label))));
    }

    /** Questions (nom → libellé, type, liste, section) et choix (liste → code → libellé) */
    public function formMeta(): array
    {
        $content = $this->asset()['content'] ?? [];
        $choices = [];
        foreach ($content['choices'] ?? [] as $c) {
            $choices[$c['list_name']][$c['name']] = self::clean(($c['label'] ?? [''])[0] ?? $c['name']);
        }
        $questions = [];
        $stack = [];
        foreach ($content['survey'] ?? [] as $r) {
            $type = $r['type'] ?? '';
            if ($type === 'begin_group' || $type === 'begin_repeat') { $stack[] = $r['name'] ?? ''; continue; }
            if ($type === 'end_group' || $type === 'end_repeat')     { array_pop($stack); continue; }
            if (!isset($r['name'])) continue;
            $section = collect($stack)->first(fn($g) => isset(self::SECTIONS[$g])) ?? ($stack[0] ?? '');
            $questions[$r['name']] = [
                'name'    => $r['name'],
                'label'   => self::clean(($r['label'] ?? [''])[0] ?? $r['name']),
                'type'    => $type,
                'list'    => $r['select_from_list_name'] ?? '',
                'section' => $section,
            ];
        }
        return ['questions' => $questions, 'choices' => $choices];
    }

    // ── Calcul ──────────────────────────────────────────────────────────────

    public function loadAll(): array
    {
        return Cache::store('file')->remember(self::COMPUTED_KEY, now()->addMinutes(10), fn() => $this->compute());
    }

    private function compute(): array
    {
        $meta    = $this->formMeta();
        $q       = $meta['questions'];
        $choices = $meta['choices'];
        $subs    = collect($this->submissions());
        $agents  = collect($this->mediaCsv('agents_terrain.csv'))->pluck('label', 'name')->all();
        $targets = collect($this->mediaCsv('menages.csv'))->keyBy('name');

        // Enrichissement REDCap : chef de ménage, téléphone, DAG
        $this->redcap->warm(['identification', 'contacts', 'dags']);
        $idRaw = $this->redcap->getIdentificationData();
        $ct    = $this->redcap->getBaselineContacts();
        $dags  = $this->redcap->getDags();
        $rc    = (new CohortHouseholds)->forCohort('1', $idRaw, $ct, [], $dags)
            ->union((new CohortHouseholds)->forCohort('2', $idRaw, $ct, [], $dags));

        $villageLbl = $choices['village'] ?? [];
        $grappeLbl  = $choices['grappe'] ?? [];
        $agentLbl   = fn($code) => $code === '' || $code === null ? '' : (preg_replace('/^Binome\s*\d+\s*:\s*/i', '', $agents[$code] ?? $code));

        $subs = $subs->map(function ($s) use ($agentLbl) {
            $s['_date']   = (string) ($s['date_visite'] ?? '');
            $s['_binome'] = (string) ($s['agent'] ?? '');
            $s['_binomeLabel'] = $agentLbl($s['agent'] ?? '');
            $s['_status'] = self::submissionStatus($s);
            return $s;
        })->sortBy(fn($s) => [$s['_date'], $s['_submission_time'] ?? ''])->values();
        $subsByHh = $subs->groupBy(fn($s) => (string) ($s['household_id'] ?? ''));

        // ── Ménages cibles et statut ──
        $households = $targets->map(function ($t, $id) use ($rc, $villageLbl, $grappeLbl, $subsByHh) {
            $r      = $rc->get($id, []);
            $list   = $subsByHh->get($id, collect());
            $done   = $list->where('_status', 'realise');
            $main   = $done->last();
            $last   = $list->last();
            $status = $main ? 'realise' : ($last['_status'] ?? 'non_visite');
            $ref    = $main ?? $last ?? [];
            return [
                'id'          => $id,
                'villageKey'  => $t['village'] ?? '',
                'village'     => $villageLbl[$t['village'] ?? ''] ?? ($t['village'] ?? '—'),
                'grappeKey'   => $t['grappe'] ?? '',
                'grappe'      => ($villageLbl[$t['village'] ?? ''] ?? '') . ' – ' . ($grappeLbl[$t['grappe'] ?? ''] ?? ($t['grappe'] ?? '')),
                'bras'        => self::brasLabel($t['bras'] ?? ''),
                'cohorte'     => $t['cohorte'] ?? '—',
                'nbOld'       => (int) ($t['nb_anciennes_mii'] ?? 0),
                'chef'        => $r['chef'] ?? '',
                'chefPhone'   => $r['chefPhone'] ?? '',
                'contact'     => $r['contact'] ?? '',
                'contactPhone'=> $r['contactPhone'] ?? '',
                'address'     => $r['address'] ?? '',
                'dag'         => $r['dag'] ?? '',
                'dagLabel'    => $r['dagLabel'] ?? 'Sans DAG',
                'status'      => $status,
                'attempts'    => $list->count(),
                'interviews'  => $done->count(),
                'date'        => $ref['_date'] ?? '',
                'binome'      => $ref['_binomeLabel'] ?? '',
                'subId'       => $main['_id'] ?? null,
                'stores'      => $main ? (($main['B1'] ?? '') === 'oui') : null,
            ];
        });

        // ── Entretiens retenus (dernier entretien consenti par ménage cible) ──
        $interviews = $households->filter(fn($h) => $h['subId'] !== null)->map(function ($h) use ($subs) {
            return $subs->firstWhere('_id', $h['subId']) + ['_bras' => $h['bras'], '_cohorte' => $h['cohorte'], '_hh' => $h];
        })->values();

        $progress = fn(Collection $g) => [
            'expected'    => $g->count(),
            'visited'     => $g->where('status', '!=', 'non_visite')->count(),
            'realise'     => $g->where('status', 'realise')->count(),
            'refus'       => $g->where('status', 'refus')->count(),
            'absent'      => $g->where('status', 'absent')->count(),
            'introuvable' => $g->where('status', 'introuvable')->count(),
            'non_visite'  => $g->where('status', 'non_visite')->count(),
            'stores'      => $g->where('stores', true)->count(),
        ];
        $groupProgress = fn(string $key) => $households->groupBy($key)->map(fn($g, $k) => ['label' => $k] + $progress($g))
            ->sortBy('label', SORT_NATURAL)->values()->all();

        // Activité par binôme (toutes les soumissions)
        $byBinome = $subs->groupBy('_binome')->map(fn($g, $code) => [
            'code'        => $code,
            'label'       => $g->first()['_binomeLabel'] ?: $code,
            'submissions' => $g->count(),
            'realise'     => $g->where('_status', 'realise')->count(),
            'refus'       => $g->where('_status', 'refus')->count(),
            'absent'      => $g->where('_status', 'absent')->count(),
            'introuvable' => $g->where('_status', 'introuvable')->count(),
            'households'  => $g->pluck('household_id')->unique()->count(),
            'first'       => $g->min('_date'),
            'last'        => $g->max('_date'),
        ])->sortBy('code', SORT_NATURAL)->values()->all();

        $byDate = $subs->groupBy('_date')->map(fn($g, $d) => [
            'date' => $d, 'submissions' => $g->count(), 'realise' => $g->where('_status', 'realise')->count(),
        ])->sortKeys()->values()->all();

        return [
            'households'  => $households->sortBy(fn($h) => [$h['village'], $h['grappe'], $h['id']])->values()->all(),
            'summary'     => $progress($households) + ['submissions' => $subs->count(), 'outside' => $subs->filter(fn($s) => !$targets->has($s['household_id'] ?? ''))->count()],
            'byVillage'   => $groupProgress('village'),
            'byGrappe'    => $groupProgress('grappe'),
            'byCohorte'   => $groupProgress('cohorte'),
            'byBras'      => $groupProgress('bras'),
            'byDag'       => $groupProgress('dagLabel'),
            'byBinome'    => $byBinome,
            'byDate'      => $byDate,
            'results'     => $this->results($interviews, $q, $choices),
            'openAnswers' => $this->openAnswers($interviews, $q),
            'photos'      => $this->photos($interviews),
            'queries'     => $this->queries($subs, $subsByHh, $targets, $households, $q),
            'formVersion' => $this->asset()['content']['settings']['version'] ?? '',
            'formName'    => $this->asset()['name'] ?? '',
            'fetchedAt'   => now()->format('d/m/Y H:i'),
        ];
    }

    private static function brasLabel(string $b): string
    {
        return match (strtoupper(trim($b))) {
            'PERMANET DUAL'  => 'PermaNet Dual',
            'INTERCEPTOR G2' => 'Interceptor G2',
            default          => $b !== '' ? ucwords(strtolower($b)) : '—',
        };
    }

    /** Statut d'une soumission : realise / refus / absent / introuvable */
    private static function submissionStatus(array $s): string
    {
        $st = $s['statut_visite'] ?? '';
        if ($st === 'entretien') return ($s['consentement'] ?? '') === 'oui' ? 'realise' : 'refus';
        return in_array($st, ['refus', 'absent', 'introuvable'], true) ? $st : 'absent';
    }

    /** Distributions des questions fermées, globalement et par bras / cohorte */
    private function results(Collection $interviews, array $q, array $choices): array
    {
        $out = [];
        foreach (self::SECTIONS as $sec => $secLabel) {
            $items = [];
            foreach ($q as $name => $def) {
                if ($def['section'] !== $sec || !in_array($def['type'], ['select_one', 'select_multiple'], true)) continue;
                $opts  = $choices[$def['list']] ?? [];
                $multi = $def['type'] === 'select_multiple';
                $tally = function (Collection $rows) use ($name, $opts, $multi) {
                    $answered = $rows->filter(fn($r) => ($r[$name] ?? '') !== '' && $r[$name] !== null);
                    $counts = array_fill_keys(array_keys($opts), 0);
                    foreach ($answered as $r) {
                        foreach ($multi ? preg_split('/\s+/', trim((string) $r[$name])) : [(string) $r[$name]] as $code) {
                            $counts[$code] = ($counts[$code] ?? 0) + 1;
                        }
                    }
                    return ['n' => $answered->count(), 'counts' => $counts];
                };
                $items[] = [
                    'name'    => $name,
                    'label'   => $def['label'],
                    'multi'   => $multi,
                    'options' => $opts,
                    'all'     => $tally($interviews),
                    'bras'    => $interviews->groupBy('_bras')->sortKeys()->map($tally)->all(),
                    'cohorte' => $interviews->groupBy('_cohorte')->sortKeys()->map($tally)->all(),
                ];
            }
            if ($items) $out[$sec] = ['label' => $secLabel, 'items' => $items];
        }
        return $out;
    }

    /** Réponses ouvertes (questions texte des sections + « Autre, précisez » + commentaire agent) */
    private function openAnswers(Collection $interviews, array $q): array
    {
        $fields = collect($q)->filter(fn($d) => $d['type'] === 'text'
            && (isset(self::SECTIONS[$d['section']]) || $d['name'] === 'commentaire_agent'))->all();
        $out = [];
        foreach ($fields as $name => $def) {
            $answers = $interviews->filter(fn($r) => trim((string) ($r[$name] ?? '')) !== '')
                ->map(fn($r) => [
                    'hh' => $r['household_id'], 'bras' => $r['_bras'], 'cohorte' => $r['_cohorte'],
                    'date' => $r['_date'], 'binome' => $r['_binomeLabel'], 'text' => trim((string) $r[$name]),
                ])->values()->all();
            if ($answers) $out[] = ['name' => $name, 'label' => $def['label'], 'section' => self::SECTIONS[$def['section']] ?? 'Commentaires', 'answers' => $answers];
        }
        return $out;
    }

    private function photos(Collection $interviews): array
    {
        $out = [];
        foreach ($interviews as $r) {
            foreach (['photo_mii' => 'Moustiquaire(s) stockée(s)', 'photo_lieu' => 'Lieu de stockage'] as $f => $lbl) {
                if (($r["{$f}_statut"] ?? '') !== 'prise') continue;
                $out[] = ['hh' => $r['household_id'], 'subId' => $r['_id'], 'field' => $f, 'label' => $lbl,
                          'bras' => $r['_bras'], 'date' => $r['_date'], 'available' => self::attachmentFor($r, $f) !== null];
            }
        }
        return $out;
    }

    /** Texte qui ressemble à une saisie de test (« hgghgh ») */
    private static function suspiciousText(string $t): bool
    {
        $t = trim($t);
        if ($t === '' ) return false;
        if (mb_strlen($t) < 4) return true;
        $letters = mb_strtolower(preg_replace('/[^\p{L}]/u', '', $t));
        if ($letters === '') return false;
        if (!preg_match('/[aeiouyàâäéèêëîïôöùûü]/u', $letters)) return true;
        return !str_contains($t, ' ') && count(array_unique(mb_str_split($letters))) <= 3;
    }

    private function queries(Collection $subs, Collection $subsByHh, Collection $targets, Collection $households, array $q): array
    {
        $out = [];
        $add = function (string $code, array $s, string $detail, string $hh = '') use (&$out) {
            [$sev, $title] = self::QUERY_CODES[$code];
            $out[] = [
                'code' => $code, 'severity' => $sev, 'title' => $title, 'detail' => $detail,
                'hh' => $hh ?: (string) ($s['household_id'] ?? ''), 'date' => $s['_date'] ?? '',
                'binome' => $s['_binomeLabel'] ?? '', 'subId' => $s['_id'] ?? null,
            ];
        };
        $openFields = collect($q)->filter(fn($d) => $d['type'] === 'text' && $d['name'] !== 'filtre_menage')->keys()->all();
        $today = now()->toDateString();

        foreach ($subs as $s) {
            $hh = (string) ($s['household_id'] ?? '');
            $t  = $targets->get($hh);
            if (!$t) {
                $add('Q-ACC-01', $s, "Soumission pour le ménage « {$hh} », absent de la liste des ménages cibles (ménages consentis avec anciennes moustiquaires à la baseline).");
            } elseif (($s['village'] ?? '') !== '' && (($s['village'] ?? '') !== ($t['village'] ?? '') || ($s['grappe'] ?? '') !== ($t['grappe'] ?? ''))) {
                $add('Q-ACC-10', $s, "Village / grappe saisis ({$s['village']} / " . ($s['grappe'] ?? '—') . ") différents de ceux de la liste ({$t['village']} / {$t['grappe']}).");
            }
            $subDate = substr((string) ($s['_submission_time'] ?? ''), 0, 10);
            $d = $s['_date'];
            if ($d === '' || $d > $today || ($subDate !== '' && $d > $subDate)) {
                $add('Q-ACC-03', $s, $d === '' ? 'Date de visite manquante.' : "Date de visite ({$d}) postérieure à la date d'envoi ({$subDate}) ou à aujourd'hui.");
            } elseif ($subDate !== '' && (strtotime($subDate) - strtotime($d)) > 7 * 86400) {
                $add('Q-ACC-11', $s, "Visite du {$d} envoyée le {$subDate}.");
            }
            if ($s['_status'] === 'refus' && trim((string) ($s['raison_refus'] ?? '')) === '') {
                $add('Q-ACC-04', $s, 'Refus (statut de visite ou consentement = Non) sans raison renseignée.');
            }
            if ($s['_status'] === 'introuvable') {
                $add('Q-ACC-09', $s, 'Ménage déclaré introuvable ou ayant déménagé : à signaler à l\'équipe de suivi (REDCap).');
            }
            if ($s['_status'] === 'realise') {
                if (($s['A1'] ?? '') === '' && ($s['B1'] ?? '') === '' && ($s['C1'] ?? '') === '') {
                    $add('Q-ACC-06', $s, 'Consentement = Oui mais les sections A, B et C sont vides (formulaire incomplet ?).');
                }
                foreach (['photo_mii', 'photo_lieu'] as $f) {
                    if (($s["{$f}_statut"] ?? '') === 'prise' && self::attachmentFor($s, $f) === null) {
                        $add('Q-ACC-05', $s, "Photo « {$f} » déclarée prise mais aucun fichier reçu (envoi incomplet depuis la tablette ?).");
                    }
                }
                $bad = collect($openFields)->filter(fn($f) => self::suspiciousText((string) ($s[$f] ?? '')))
                    ->map(fn($f) => "{$f} = « " . mb_substr(trim((string) $s[$f]), 0, 30) . ' »')->values();
                if ($bad->isNotEmpty()) {
                    $add('Q-ACC-07', $s, 'Réponse(s) ouverte(s) sans contenu exploitable : ' . $bad->implode(' ; ') . '.');
                }
            }
        }

        foreach ($subsByHh as $hh => $list) {
            $done = $list->where('_status', 'realise');
            if ($done->count() > 1) {
                $add('Q-ACC-02', $done->last(), "{$done->count()} entretiens consentis pour ce ménage (" . $done->pluck('_date')->implode(', ') . '). Seul le plus récent est retenu dans les résultats.', $hh);
            }
            if ($done->isEmpty() && $list->count() >= 3 && $targets->has($hh)) {
                $add('Q-ACC-08', $list->last(), "{$list->count()} visites sans entretien (" . $list->pluck('_status')->map(fn($x) => self::STATUS_LABELS[$x] ?? $x)->implode(', ') . ').', $hh);
            }
        }

        $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($out, fn($a, $b) => [$order[$a['severity']], $a['code'], $a['hh']] <=> [$order[$b['severity']], $b['code'], $b['hh']]);
        return $out;
    }
}
