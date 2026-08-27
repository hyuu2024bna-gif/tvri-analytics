<?php

namespace App\Http\Controllers;

use App\Exports\ContentReportExport;
use App\Models\ChannelStatsDaily;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function form()
    {
        return view('reports.form');
    }

    public function exportPdf(Request $request)
    {
        $validated = $this->validatePeriod($request);
        $rows = $this->getReportRows($validated['start_date'], $validated['end_date']);

        set_time_limit(180);

        $pdf = Pdf::loadView('reports.pdf', [
            'rows' => $rows,
            'startDate' => $validated['start_date'],
            'endDate' => $validated['end_date'],
            'totalViews' => $rows->sum('views'),
        ])->setPaper('a4', 'landscape');

        $filename = "laporan-konten-tvri-aceh_{$validated['start_date']}_{$validated['end_date']}.pdf";

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $validated = $this->validatePeriod($request);
        $rows = $this->getReportRows($validated['start_date'], $validated['end_date']);

        set_time_limit(180);

        $filename = "laporan-konten-tvri-aceh_{$validated['start_date']}_{$validated['end_date']}.xlsx";

        return Excel::download(new ContentReportExport($rows), $filename);
    }

    public function exportVisualPdf(Request $request)
    {
        $validated = $this->validatePeriod($request);
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        set_time_limit(180);

        $rows = $this->getReportRows($startDate, $endDate);

        $totalPost = $rows->count();
        $totalViews = $rows->sum('views');
        $totalLikes = $rows->sum('likes');
        $totalComments = $rows->sum('comments');
        $top3 = $rows->take(3)->values();

        $subsInRange = ChannelStatsDaily::whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->get();

        $subsStart = $subsInRange->first();
        $subsEnd = $subsInRange->last();

        $latestOverall = ChannelStatsDaily::orderByDesc('tanggal')->first();
        $subscriberCurrent = $latestOverall->subscriber_count ?? 0;
        $latestSyncDate = $latestOverall->tanggal ?? null;

        $subscriberGrowth = ($subsStart && $subsEnd)
            ? $subsEnd->subscriber_count - $subsStart->subscriber_count
            : 0;

        $chartUrl = $this->buildSubscriberChartUrl($subsInRange);

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])
            ->loadView('reports.pdf-visual', [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalPost' => $totalPost,
                'totalViews' => $totalViews,
                'totalLikes' => $totalLikes,
                'totalComments' => $totalComments,
                'subscriberCurrent' => $subscriberCurrent,
                'subscriberGrowth' => $subscriberGrowth,
                'latestSyncDate' => $latestSyncDate,
                'chartUrl' => $chartUrl,
                'top3' => $top3,
            ])
            ->setPaper('a4', 'portrait');

        $filename = "laporan-visual-youtube-tvri-aceh_{$startDate}_{$endDate}.pdf";

        return $pdf->download($filename);
    }

    private function buildSubscriberChartUrl($subsInRange): ?string
    {
        if ($subsInRange->count() < 2) {
            return null;
        }

        $values = $subsInRange->pluck('subscriber_count');
        $min = $values->min();
        $max = $values->max();

        $range = max($max - $min, 10);
        $padding = $range * 0.3;
        $suggestedMin = max(0, floor($min - $padding));
        $suggestedMax = ceil($max + $padding);

        $config = [
            'type' => 'line',
            'data' => [
                'labels' => $subsInRange->pluck('tanggal')->map(fn ($t) => (string) $t)->toArray(),
                'datasets' => [[
                    'label' => 'Subscribers',
                    'data' => $values->toArray(),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37,99,235,0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'y' => [
                        'suggestedMin' => $suggestedMin,
                        'suggestedMax' => $suggestedMax,
                    ],
                ],
            ],
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config))
            . '&width=650&height=280&backgroundColor=white';
    }

    private function validatePeriod(Request $request): array
    {
        return $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
    }

    private function getReportRows(string $startDate, string $endDate)
    {
        return DB::table('content_stats_daily as csd')
            ->join(
                DB::raw('(select content_id, max(tanggal) as max_tanggal from content_stats_daily group by content_id) latest'),
                function ($join) {
                    $join->on('csd.content_id', '=', 'latest.content_id')
                         ->on('csd.tanggal', '=', 'latest.max_tanggal');
                }
            )
            ->join('contents', 'contents.id', '=', 'csd.content_id')
            ->where('contents.status', 'aktif')
            ->whereBetween('contents.tanggal_upload', [$startDate, $endDate])
            ->select(
                'contents.judul', 'contents.url', 'contents.tanggal_upload', 'contents.thumbnail_url',
                'csd.views', 'csd.likes', 'csd.comments'
            )
            ->orderByDesc('csd.views')
            ->get();
    }
}
