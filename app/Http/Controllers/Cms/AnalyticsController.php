<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Services\MatomoReporting;
use App\Support\Matomo;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * The analytics dashboard. Reads Matomo's Reporting API server-side (the token
 * never leaves the server) and hands the parsed metrics to Inertia. When
 * Matomo is not configured yet, it renders a "connect it" state instead.
 */
class AnalyticsController extends Controller
{
    /** Allowed look-back windows, in days. */
    private const RANGES = [7, 30, 90, 365];

    public function index(Request $request, MatomoReporting $matomo): Response
    {
        if (! Matomo::reportingReady()) {
            return inertia('cms/analytics/index', [
                'configured' => false,
                'matomoUrl' => Matomo::url(),
            ]);
        }

        $range = (int) $request->integer('range');
        $range = in_array($range, self::RANGES, true) ? $range : 30;
        $date = "last{$range}";

        $summary = $matomo->get('VisitsSummary.get', ['period' => 'range', 'date' => $date]);
        $actions = $matomo->get('Actions.get', ['period' => 'range', 'date' => $date]);
        $series = $matomo->get('VisitsSummary.get', ['period' => 'day', 'date' => $date]);
        $pages = $matomo->get('Actions.getPageUrls', ['period' => 'range', 'date' => $date, 'flat' => 1, 'filter_limit' => 8]);
        $referrers = $matomo->get('Referrers.getReferrerType', ['period' => 'range', 'date' => $date, 'filter_limit' => 6]);
        $countries = $matomo->get('UserCountry.getCountry', ['period' => 'range', 'date' => $date, 'filter_limit' => 6]);
        $devices = $matomo->get('DevicesDetection.getType', ['period' => 'range', 'date' => $date, 'filter_limit' => 6]);

        return inertia('cms/analytics/index', [
            'configured' => true,
            // If even the summary failed the token/URL is probably wrong or
            // Matomo is unreachable — the page shows a hint.
            'reachable' => $summary !== null,
            'range' => $range,
            'ranges' => self::RANGES,
            'matomoUrl' => Matomo::url(),
            'summary' => $this->summary($summary ?? [], $actions ?? []),
            'series' => $this->series($series ?? []),
            'topPages' => $this->rows($pages ?? [], 'nb_hits'),
            'referrers' => $this->rows($referrers ?? [], 'nb_visits'),
            'countries' => $this->rows($countries ?? [], 'nb_visits'),
            'devices' => $this->rows($devices ?? [], 'nb_visits'),
        ]);
    }

    /**
     * @param  array<int|string, mixed>  $summary
     * @param  array<int|string, mixed>  $actions
     * @return array<string, int|float|string>
     */
    private function summary(array $summary, array $actions): array
    {
        return [
            'visits' => (int) ($summary['nb_visits'] ?? 0),
            'uniqueVisitors' => (int) ($summary['nb_uniq_visitors'] ?? 0),
            'pageviews' => (int) ($actions['nb_pageviews'] ?? $summary['nb_actions'] ?? 0),
            'bounceRate' => (string) ($summary['bounce_rate'] ?? '0%'),
            'avgTimeSeconds' => (int) ($summary['avg_time_on_site'] ?? 0),
        ];
    }

    /**
     * Per-day visits for the trend chart, oldest first.
     *
     * @param  array<int|string, mixed>  $series
     * @return list<array{date: string, visits: int}>
     */
    private function series(array $series): array
    {
        $points = [];

        foreach ($series as $date => $day) {
            $points[] = [
                'date' => (string) $date,
                'visits' => is_array($day) ? (int) ($day['nb_visits'] ?? 0) : 0,
            ];
        }

        return $points;
    }

    /**
     * Normalise a report list into {label, value} rows.
     *
     * @param  array<int|string, mixed>  $report
     * @return list<array{label: string, value: int}>
     */
    private function rows(array $report, string $valueKey): array
    {
        $rows = [];

        foreach ($report as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = [
                'label' => (string) ($row['label'] ?? '—'),
                'value' => (int) ($row[$valueKey] ?? 0),
            ];
        }

        return $rows;
    }
}
