<?php

namespace App\Http\Controllers;

use App\Services\CohortAFollowUpService;
use App\Services\AccumulationMiiService;
use App\Services\CohortBFollowUpService;
use App\Services\CohortBNetSelectionService;
use App\Services\CohortHouseholds;
use App\Services\RedCapService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Suivi des cohortes A et B — suivi individuel des ménages et listes PDF par DAG.
 * La cohorte est fournie par la route (defaults('cohort', 'a'|'b')).
 */
class SuiviCohorteController extends Controller
{
    /** Configuration propre à chaque cohorte */
    private function cfg(Request $request): array
    {
        return match ($request->route('cohort')) {
            'b' => [
                'letter'       => 'B',
                'route'        => 'suivi-b.menages',
                'service'      => CohortBFollowUpService::class,
                'computedKey'  => CohortBFollowUpService::COMPUTED_CACHE_KEY,
                'cachePrefix'  => 'fu_b',
                'events'       => RedCapService::COHORT_B_EVENTS,
                'suivis'       => CohortBFollowUpService::FU_B_LABELS,
                'statusLabels' => CohortBFollowUpService::STATUS_LABELS,
                'short'        => ['non_visite' => 'Non visité', 'en_cours' => 'Partiel', 'realise' => 'Complet'],
                'doneStatuses' => ['realise'],
                'forms'        => 'Form 10 uniquement — une instance par moustiquaire de l\'étude : vérifier, prélever, puis remplacer (saisir le code de la nouvelle moustiquaire).',
                'netsHeader'   => 'Moustiquaires à prélever (n° : code)',
                'replacementColumn' => true,
                'footnote'     => 'Statuts : <b>Non visité</b> = aucun Form 10 · <b>Partiel</b> = moins de Form 10 que de moustiquaires distribuées · '
                                . '<b>Complet</b> = un Form 10 par moustiquaire. « Moustiq. » = vérifiées / distribuées à la baseline.',
            ],
            default => [
                'letter'       => 'A',
                'route'        => 'suivi-a.menages',
                'pdfView'      => 'suivi-a-menages-pdf',
                'service'      => CohortAFollowUpService::class,
                'computedKey'  => CohortAFollowUpService::COMPUTED_CACHE_KEY,
                'cachePrefix'  => 'fu_a',
                'events'       => RedCapService::COHORT_A_EVENTS,
                'suivis'       => CohortAFollowUpService::FU_A_LABELS,
                'statusLabels' => CohortAFollowUpService::STATUS_LABELS,
                'short'        => ['non_visite' => 'Non visité', 'en_cours' => 'En cours', 'non_realise' => 'Non réalisé', 'realise' => 'Réalisé'],
                'doneStatuses' => ['realise', 'non_realise'],
                'forms'        => 'Formulaires : Visite Ménage · Questionnaire Base · Membres · Avant Suivi · Form 7 (une instance par moustiquaire) · Form 8 · Form 9 (si autres moustiquaires)',
                'netsHeader'   => 'Moustiquaires de l\'étude (n° : code)',
                'replacementColumn' => false,
                'footnote'     => 'Statuts : <b>Non visité</b> = aucune donnée à cet événement · <b>En cours</b> = données saisies mais « suivi réalisé » non renseigné · '
                                . '<b>Non réalisé</b> / <b>Réalisé</b> = réponse au Formulaire Avant Suivi (Q5). « Moustiq. » = présentes / distribuées à la baseline.',
            ],
        };
    }

    /** GET /suivi-{a|b}/menages — suivi individuel des ménages + export PDF par DAG */
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        set_time_limit(300);
        $cfg = $this->cfg($request);

        if ($request->boolean('refresh')) {
            // Recharger les données de suivi depuis REDCap (les données baseline restent en cache)
            Cache::store('file')->forget($cfg['computedKey']);
            foreach ($cfg['events'] as $event) {
                Cache::forget("redcap.{$cfg['cachePrefix']}.$event");
            }
            return redirect()->route($cfg['route'], $request->except(['refresh', 'page']));
        }

        $data    = app($cfg['service'])->loadAll();
        $suivi   = $this->validSuivi($cfg, $request->query('suivi'));
        $filters = [
            'suivi'   => $suivi,
            'dag'     => (string) $request->query('dag', ''),
            'village' => (string) $request->query('village', ''),
            'tablet'  => (string) $request->query('tablet', ''),
            'status'  => (string) $request->query('status', ''),
            'search'  => trim((string) $request->query('search', '')),
        ];

        $all  = $this->rows($data, $suivi);
        $rows = $all
            ->when($filters['dag'] !== '',     fn($c) => $c->where('dag', $filters['dag']))
            ->when($filters['village'] !== '', fn($c) => $c->where('village', $filters['village']))
            ->when($filters['tablet'] !== '',  fn($c) => $c->where('tablet', $filters['tablet']))
            ->when($filters['status'] !== '',  fn($c) => $c->filter(fn($r) => $r['current']['status'] === $filters['status']))
            ->when($filters['search'] !== '',  fn($c) => $c->filter(fn($r) =>
                str_contains(mb_strtolower($r['id'] . ' ' . $r['chef'] . ' ' . $r['contact'] . ' ' . $r['chefPhone']), mb_strtolower($filters['search']))))
            ->values();

        // Avancement par DAG pour le suivi sélectionné
        $byDag = $all->groupBy('dag')->map(fn($g, $dag) => [
            'dag'        => $dag,
            'label'      => $g->first()['dagLabel'],
            'expected'   => $g->count(),
            'visited'    => $g->filter(fn($r) => $r['current']['status'] !== 'non_visite')->count(),
            'done'       => $g->filter(fn($r) => $r['current']['status'] === 'realise')->count(),
            'notDone'    => $g->filter(fn($r) => $r['current']['status'] === 'non_realise')->count(),
            'inProgress' => $g->filter(fn($r) => $r['current']['status'] === 'en_cours')->count(),
        ])->sortBy('label', SORT_NATURAL)->values();

        // Avancement par village × tablette (les n° de tablette se répètent d'un village à l'autre)
        $byTablet = $all->groupBy(fn($r) => $r['village'] . '|' . $r['tablet'])->map(fn($g) => [
            'village'    => $g->first()['village'],
            'tablet'     => (string) $g->first()['tablet'],
            'clusters'   => $g->pluck('cluster')->unique()->sort()->implode(', '),
            'dags'       => $g->pluck('dagLabel')->unique()->sort()->implode(', '),
            'expected'   => $g->count(),
            'visited'    => $g->filter(fn($r) => $r['current']['status'] !== 'non_visite')->count(),
            'done'       => $g->filter(fn($r) => in_array($r['current']['status'], $cfg['doneStatuses'], true))->count(),
        ])->sortBy(fn($t) => [$t['village'], $t['tablet']])->values();

        // Pagination (le rendu de 1 000+ lignes alourdit fortement la page)
        $perPage = 100;
        $pages   = max(1, (int) ceil($rows->count() / $perPage));
        $page    = min($pages, max(1, (int) $request->query('page', 1)));

        return view('suivi-menages', [
            'cfg'          => $cfg,
            'rows'         => $rows->slice(($page - 1) * $perPage, $perPage), // clés conservées → numérotation continue
            'filteredCount'=> $rows->count(),
            'page'         => $page,
            'pages'        => $pages,
            'total'        => $all->count(),
            'byDag'        => $byDag,
            'members'      => CohortHouseholds::baselineMembers(app(RedCapService::class)->getMemberData()),
            'byTablet'     => $byTablet->when($filters['village'] !== '', fn($c) => $c->where('village', $filters['village']))->values(),
            'tablets'      => $all->pluck('tablet')->unique()->sort()->values(),
            'statusCounts' => $all->countBy(fn($r) => $r['current']['status'])->all(),
            'dags'         => $data['dags'],
            'villages'     => $all->pluck('village')->unique()->sort()->values(),
            'filters'      => $filters,
            'suivis'       => $cfg['suivis'],
            'statusLabels' => $cfg['statusLabels'],
        ]);
    }

    /** GET /suivi-{a|b}/menages/pdf?suivi=suivi6&dag=dagteam1&only=remaining[&format=html] */
    public function pdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(300);
        $cfg   = $this->cfg($request);
        $data  = app($cfg['service'])->loadAll();
        $suivi = $this->validSuivi($cfg, $request->query('suivi'));
        $dag    = (string) $request->query('dag', '');
        $tablet  = (string) $request->query('tablet', '');
        $village = (string) $request->query('village', '');
        $only    = $request->query('only') === 'remaining' ? 'remaining' : 'all';

        $rows = $this->rows($data, $suivi)
            ->when($dag !== '', fn($c) => $c->where('dag', $dag))
            ->when($tablet !== '', fn($c) => $c->where('tablet', $tablet))
            ->when($village !== '', fn($c) => $c->where('village', $village))
            ->when($only === 'remaining', fn($c) => $c->filter(fn($r) => !in_array($r['current']['status'], $cfg['doneStatuses'], true)));

        // Une section (page) par DAG
        $groups = $rows->groupBy('dag')
            ->map(fn($g) => [
                'label' => $g->first()['dagLabel'],
                'rows'  => $g->sortBy(fn($r) => [$r['village'], $r['cluster'], $r['id']])->values(),
            ])
            ->sortBy('label', SORT_NATURAL)->values();

        $suiviLabel = $cfg['suivis'][$suivi];
        $viewData = [
            'cfg'          => $cfg,
            'groups'       => $groups,
            'suiviLabel'   => $suiviLabel,
            'only'         => $only,
            'statusLabels' => $cfg['statusLabels'],
            'tablet'       => $tablet,
            'village'      => $village,
            // Membres du ménage enregistrés à la baseline (nom, sexe, âge)
            'members'      => CohortHouseholds::baselineMembers(app(RedCapService::class)->getMemberData()),
        ];
        $pdfView = $cfg['pdfView'] ?? 'suivi-menages-pdf';

        // Cohorte A : une section par village / grappe, avec en bas les ménages Cohorte B sélectionnés
        // (même suivi, même grappe) et le croisement avec le formulaire Kobo « Accumulation MII ».
        if ($cfg['letter'] === 'A') {
            $allRows = $this->rows($data, $suivi);
            $viewData['sections'] = $this->clusterSections($allRows, $rows, $dag, $village, $tablet, $suivi, $only);
            // Le formulaire Kobo « Accumulation MII » est administré au suivi 6 mois uniquement
            $viewData['showKobo'] = $suivi === 'suivi6';
            [$viewData['kobo'], $viewData['koboError']] = $viewData['showKobo'] ? $this->koboStatus() : [[], null];
            $viewData['koboLabels'] = AccumulationMiiService::STATUS_LABELS;
            $viewData['dagLabel']   = $dag !== '' ? ($allRows->firstWhere('dag', $dag)['dagLabel'] ?? $dag) : 'Tous les DAG';
        }

        // Version imprimable dans le navigateur (instantanée, conseillée pour tous les DAG)
        if ($request->query('format') === 'html') {
            return response()->view($pdfView, $viewData + ['printMode' => true]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($pdfView, $viewData);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'isPhpEnabled'         => false,
            'defaultFont'          => 'dejavu sans',
            'dpi'                  => 96,
        ]);

        // Pied de page + numérotation (appliqué à toutes les pages après rendu)
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font   = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $grey   = [0.4, 0.45, 0.55];
        $canvas->page_text(28, $canvas->get_height() - 24,
            "ENDURE-Net · Suivi Cohorte {$cfg['letter']} · {$suiviLabel} · imprimé le " . now()->format('d/m/Y H:i'), $font, 7, $grey);
        $canvas->page_text($canvas->get_width() - 90, $canvas->get_height() - 24,
            'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 7, $grey);

        $dagSlug = ($dag !== '' ? '_' . ($groups->first()['label'] ?? $dag) : ($tablet !== '' ? '' : '_tous-DAG'))
                 . ($village !== '' ? '_' . str_replace(' ', '-', $village) : '')
                 . ($tablet !== '' ? "_tablette-{$tablet}" : '');
        $name = "suivi-cohorte-{$cfg['letter']}_" . str_replace(' ', '', $suiviLabel) . $dagSlug
              . ($only === 'remaining' ? '_restants' : '') . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($name);
    }

    /**
     * Sections village / grappe pour la liste Cohorte A. Les grappes retenues sont celles des ménages
     * Cohorte A du DAG demandé (tous statuts), afin de garder une grappe dont il ne reste que des
     * ménages Cohorte B à visiter. Avec une tablette, seuls les ménages A et B de cette tablette sont listés.
     */
    private function clusterSections(Collection $allA, Collection $rowsA, string $dag, string $village, string $tablet, string $suivi, string $only): Collection
    {
        $key      = fn($r) => ($r['village'] ?? '') . '|' . ($r['cluster'] ?? '');
        $clusters = $allA->when($dag !== '', fn($c) => $c->where('dag', $dag))
                         ->when($village !== '', fn($c) => $c->where('village', $village))
                         ->when($tablet !== '', fn($c) => $c->where('tablet', $tablet))->groupBy($key);
        $rowsByCl = $rowsA->groupBy($key);

        // Ménages Cohorte B sélectionnés (non libérés) au même suivi
        $bByCl = collect();
        if (array_key_exists($suivi, CohortBFollowUpService::FU_B_LABELS)) {
            $bByCl = collect(app(CohortBNetSelectionService::class)->state($suivi))
                ->flatMap(fn($a) => $a['state']['rows'] ?? [])
                ->filter(fn($r) => $only === 'remaining' ? $r['state'] === 'pending' : $r['state'] !== 'released')
                ->filter(fn($r) => $tablet === '' || CohortHouseholds::tablet($r['id']) === $tablet)
                ->filter(fn($r) => $village === '' || ($r['village'] ?? '') === $village)
                ->sortBy('rank')
                ->groupBy($key);

            // Tablette : ajouter les grappes où la tablette n'a que des ménages Cohorte B
            if ($tablet !== '') {
                foreach ($bByCl as $k => $b) {
                    if (!$clusters->has($k)) $clusters->put($k, collect([$b->first() + ['dagLabel' => $b->first()['dagLabel'] ?? '']]));
                }
            }
        }

        return $clusters->map(function ($g, $k) use ($rowsByCl, $bByCl) {
            $first = $g->first();
            return [
                'village' => $first['village'],
                'cluster' => $first['cluster'],
                'brasKey' => $first['brasKey'],
                'bras'    => $first['bras'],
                'dags'    => $g->pluck('dagLabel')->unique()->sort()->implode(', '),
                'rowsA'   => $rowsByCl->get($k, collect())->sortBy('id')->values(),
                'rowsB'   => $bByCl->get($k, collect())->values(),
            ];
        })
        ->filter(fn($s) => $s['rowsA']->isNotEmpty() || $s['rowsB']->isNotEmpty())
        ->sortBy(fn($s) => [$s['village'], $s['cluster']])
        ->values();
    }

    /** Statut Kobo « Accumulation MII » par ménage (ménages de la liste cible menages.csv) */
    private function koboStatus(): array
    {
        try {
            $kobo = collect(app(AccumulationMiiService::class)->loadAll()['households'] ?? [])
                ->mapWithKeys(fn($h) => [$h['id'] => $h['status']])->all();
            return [$kobo, null];
        } catch (\Throwable $e) {
            report($e);
            return [[], 'Formulaire Kobo indisponible : ' . $e->getMessage()];
        }
    }

    private function validSuivi(array $cfg, ?string $s): string
    {
        return array_key_exists((string) $s, $cfg['suivis']) ? $s : array_key_first($cfg['suivis']);
    }

    /** Une ligne par ménage : infos baseline + statut à chaque suivi */
    private function rows(array $data, string $suivi): Collection
    {
        $empty = ['status' => 'non_visite', 'date' => '', 'agent' => '', 'missing' => [], 'notDoneReason' => '',
                  'moved' => false, 'netsAssessed' => 0, 'netsPresent' => 0, 'netsExpected' => 0];

        return $data['households']->map(function ($h, $id) use ($data, $suivi, $empty) {
            $byTp = [];
            foreach ($data['timepoints'] as $k => $tp) {
                $byTp[$k] = $tp['statusById'][$id] ?? $empty;
            }
            return $h + ['tablet' => CohortHouseholds::tablet($id), 'timepoints' => $byTp, 'current' => $byTp[$suivi]];
        })
        ->sortBy(fn($r) => [$r['dagLabel'], $r['village'], $r['cluster'], $r['id']])
        ->values();
    }
}
