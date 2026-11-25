<?php

namespace App\Http\Controllers;

use App\Services\AccumulationMiiService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Formulaire Kobo « Accumulation, stockage et utilisation des MII » (suivi 6 mois) :
 * avancement, ménages restants (PDF), résultats, réponses ouvertes, queries.
 */
class AccumulationMiiController extends Controller
{
    private const TABS = [
        'avancement' => 'Avancement',
        'menages'    => 'Ménages',
        'resultats'  => 'Résultats',
        'ouvertes'   => 'Réponses ouvertes',
        'queries'    => 'Queries',
    ];

    public function __construct(private AccumulationMiiService $svc) {}

    /** GET /accumulation-mii */
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        set_time_limit(300);
        if ($request->boolean('refresh')) {
            $this->svc->clearCache();
            return redirect()->route('accumulation.index', $request->except(['refresh', 'page']));
        }

        try {
            $data = $this->svc->loadAll();
        } catch (\Throwable $e) {
            report($e);
            return view('accumulation-mii', ['error' => $e->getMessage(), 'tab' => 'avancement', 'tabs' => self::TABS]);
        }

        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'avancement';
        $filters = $this->filters($request);
        $view = ['data' => $data, 'tab' => $tab, 'tabs' => self::TABS, 'filters' => $filters,
                 'statusLabels' => AccumulationMiiService::STATUS_LABELS, 'statusColors' => AccumulationMiiService::STATUS_COLORS];

        $all = collect($data['households']);
        $view['villages'] = $all->pluck('village')->unique()->sort()->values();
        $view['grappes']  = $all->when($filters['village'] !== '', fn($c) => $c->where('village', $filters['village']))
                                ->pluck('grappe')->unique()->sort(SORT_NATURAL)->values();
        $view['dags']     = $all->pluck('dagLabel')->unique()->sort(SORT_NATURAL)->values();

        if ($tab === 'menages') {
            $rows    = $this->filtered($all, $filters);
            $perPage = 100;
            $pages   = max(1, (int) ceil($rows->count() / $perPage));
            $page    = min($pages, max(1, (int) $request->query('page', 1)));
            $view   += ['rows' => $rows->slice(($page - 1) * $perPage, $perPage), 'filteredCount' => $rows->count(),
                        'page' => $page, 'pages' => $pages];
        }
        if ($tab === 'resultats') {
            $view['compare'] = in_array($request->query('compare'), ['bras', 'cohorte'], true) ? $request->query('compare') : 'bras';
        }
        if ($tab === 'queries') {
            $sev  = (string) $request->query('severity', '');
            $code = (string) $request->query('code', '');
            $view['queries'] = collect($data['queries'])
                ->when($sev !== '',  fn($c) => $c->where('severity', $sev))
                ->when($code !== '', fn($c) => $c->where('code', $code))->values();
            $view['querySev']  = $sev;
            $view['queryCode'] = $code;
            $view['queryCodes'] = AccumulationMiiService::QUERY_CODES;
        }
        return view('accumulation-mii', $view);
    }

    /** GET /accumulation-mii/pdf — liste des ménages par village / grappe (restants par défaut) */
    public function pdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(300);
        $data    = $this->svc->loadAll();
        $filters = $this->filters($request);
        $only    = $request->query('only', 'remaining') === 'all' ? 'all' : 'remaining';

        $rows = $this->filtered(collect($data['households']), $filters)
            ->when($only === 'remaining', fn($c) => $c->where('status', '!=', 'realise'));

        $groups = $rows->groupBy('grappe')
            ->map(fn($g, $k) => ['label' => $k, 'rows' => $g->sortBy('id')->values()])
            ->sortBy('label', SORT_NATURAL)->values();

        $viewData = ['groups' => $groups, 'only' => $only, 'statusLabels' => AccumulationMiiService::STATUS_LABELS,
                     'formName' => $data['formName']];

        if ($request->query('format') === 'html') {
            return response()->view('accumulation-mii-pdf', $viewData + ['printMode' => true]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accumulation-mii-pdf', $viewData);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false, 'isPhpEnabled' => false,
                         'defaultFont' => 'dejavu sans', 'dpi' => 96]);
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font   = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $grey   = [0.4, 0.45, 0.55];
        $canvas->page_text(28, $canvas->get_height() - 24,
            'ENDURE-Net · Accumulation et stockage des MII · Suivi 6 mois · imprimé le ' . now()->format('d/m/Y H:i'), $font, 7, $grey);
        $canvas->page_text($canvas->get_width() - 90, $canvas->get_height() - 24, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 7, $grey);

        $scope = $filters['grappe'] ?: ($filters['village'] ?: 'tous-villages');
        $name  = 'accumulation-mii_' . str_replace([' ', '–'], ['', '-'], $scope) . ($only === 'remaining' ? '_restants' : '') . '_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($name);
    }

    /** GET /accumulation-mii/photo/{submission}/{field} — photo jointe (relais authentifié vers Kobo) */
    public function photo(int $submission, string $field)
    {
        abort_unless(in_array($field, ['photo_mii', 'photo_lieu'], true), 404);
        $photo = $this->svc->photo($submission, $field);
        abort_if($photo === null, 404, 'Photo non disponible');
        return response($photo[0], 200, ['Content-Type' => $photo[1], 'Cache-Control' => 'private, max-age=3600']);
    }

    private function filters(Request $request): array
    {
        return [
            'village' => (string) $request->query('village', ''),
            'grappe'  => (string) $request->query('grappe', ''),
            'cohorte' => (string) $request->query('cohorte', ''),
            'bras'    => (string) $request->query('bras', ''),
            'dag'     => (string) $request->query('dag', ''),
            'status'  => (string) $request->query('status', ''),
            'search'  => trim((string) $request->query('search', '')),
        ];
    }

    private function filtered(Collection $rows, array $f): Collection
    {
        return $rows
            ->when($f['village'] !== '', fn($c) => $c->where('village', $f['village']))
            ->when($f['grappe'] !== '',  fn($c) => $c->where('grappe', $f['grappe']))
            ->when($f['cohorte'] !== '', fn($c) => $c->where('cohorte', $f['cohorte']))
            ->when($f['bras'] !== '',    fn($c) => $c->where('bras', $f['bras']))
            ->when($f['dag'] !== '',     fn($c) => $c->where('dagLabel', $f['dag']))
            ->when($f['status'] === 'restants', fn($c) => $c->where('status', '!=', 'realise'))
            ->when($f['status'] !== '' && $f['status'] !== 'restants', fn($c) => $c->where('status', $f['status']))
            ->when($f['search'] !== '',  fn($c) => $c->filter(fn($r) =>
                str_contains(mb_strtolower($r['id'] . ' ' . $r['chef'] . ' ' . $r['contact'] . ' ' . $r['chefPhone']), mb_strtolower($f['search']))))
            ->values();
    }
}
