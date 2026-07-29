import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowUpRight, BarChart3, Clock, Eye, MousePointerClick, Users } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { edit as settingsEdit } from '@/routes/cms/settings';
import type { BreadcrumbItem } from '@/types';

interface Row {
    label: string;
    value: number;
}

interface Props {
    configured: boolean;
    matomoUrl: string | null;
    reachable?: boolean;
    range?: number;
    ranges?: number[];
    summary?: {
        visits: number;
        uniqueVisitors: number;
        pageviews: number;
        bounceRate: string;
        avgTimeSeconds: number;
    };
    series?: Array<{ date: string; visits: number }>;
    topPages?: Row[];
    referrers?: Row[];
    countries?: Row[];
    devices?: Row[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'التحليلات', href: '/cms/analytics' },
];

const numberFmt = new Intl.NumberFormat('ar-EG');

function formatDuration(seconds: number): string {
    const m = Math.floor(seconds / 60);
    const s = Math.round(seconds % 60);

    return `${m}:${s.toString().padStart(2, '0')}`;
}

export default function Analytics({
    configured,
    matomoUrl,
    reachable = true,
    range = 30,
    ranges = [7, 30, 90, 365],
    summary,
    series = [],
    topPages = [],
    referrers = [],
    countries = [],
    devices = [],
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="التحليلات" />

            <div className="space-y-6 p-4 sm:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="font-display text-2xl font-bold">التحليلات</h1>
                    {matomoUrl && (
                        <a href={matomoUrl} target="_blank" rel="noreferrer noopener">
                            <Button variant="outline" size="sm">
                                فتح Matomo <ArrowUpRight className="size-4" />
                            </Button>
                        </a>
                    )}
                </div>

                {!configured ? (
                    <NotConfigured />
                ) : (
                    <>
                        {!reachable && (
                            <div className="flex items-start gap-2 rounded-lg border border-[color-mix(in_srgb,var(--color-druze-yellow)_40%,transparent)] bg-[color-mix(in_srgb,var(--color-druze-yellow)_10%,transparent)] p-3 text-sm">
                                <AlertTriangle className="mt-0.5 size-4 shrink-0 text-[color-mix(in_srgb,var(--color-druze-yellow)_70%,black)]" />
                                <span>
                                    تعذّر جلب البيانات من Matomo. تأكّد من صحة العنوان ورمز الوصول في{' '}
                                    <Link href={settingsEdit().url} className="font-semibold text-druze-blue hover:underline">
                                        الإعدادات
                                    </Link>
                                    .
                                </span>
                            </div>
                        )}

                        {/* Range selector */}
                        <div className="flex flex-wrap gap-1.5">
                            {ranges.map((r) => (
                                <Link
                                    key={r}
                                    href={`/cms/analytics?range=${r}`}
                                    preserveScroll
                                    className={
                                        r === range
                                            ? 'rounded-full bg-druze-blue px-3 py-1 text-sm font-medium text-white'
                                            : 'rounded-full bg-muted px-3 py-1 text-sm font-medium text-muted-foreground hover:text-foreground'
                                    }
                                >
                                    {r === 365 ? 'سنة' : `${r} يوم`}
                                </Link>
                            ))}
                        </div>

                        {/* Stat tiles */}
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            <StatTile icon={Users} label="الزيارات" value={numberFmt.format(summary?.visits ?? 0)} />
                            <StatTile icon={Users} label="زوّار فريدون" value={numberFmt.format(summary?.uniqueVisitors ?? 0)} />
                            <StatTile icon={Eye} label="مشاهدات الصفحات" value={numberFmt.format(summary?.pageviews ?? 0)} />
                            <StatTile icon={MousePointerClick} label="معدّل الارتداد" value={summary?.bounceRate ?? '—'} />
                            <StatTile icon={Clock} label="متوسط الزيارة" value={formatDuration(summary?.avgTimeSeconds ?? 0)} />
                        </div>

                        {/* Visits trend */}
                        <section className="rounded-xl border border-border bg-card p-5">
                            <h2 className="mb-4 font-display text-lg font-bold">الزيارات عبر الوقت</h2>
                            <VisitsChart series={series} />
                        </section>

                        <div className="grid gap-6 lg:grid-cols-2">
                            <RankList title="أكثر الصفحات زيارة" rows={topPages} />
                            <RankList title="مصادر الزيارات" rows={referrers} />
                            <RankList title="الدول" rows={countries} />
                            <RankList title="الأجهزة" rows={devices} />
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}

function NotConfigured() {
    return (
        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed border-border bg-card px-6 py-16 text-center">
            <span className="flex size-14 items-center justify-center rounded-full bg-druze-blue-tint text-druze-blue">
                <BarChart3 className="size-7" />
            </span>
            <div className="space-y-1">
                <h2 className="font-display text-lg font-bold">لم يتم ربط Matomo بعد</h2>
                <p className="mx-auto max-w-md text-sm text-muted-foreground">
                    أضِف عنوان Matomo ومعرّف الموقع ورمز الوصول في الإعدادات لعرض تحليلات الزوّار هنا.
                </p>
            </div>
            <Link href={settingsEdit().url}>
                <Button>الذهاب إلى الإعدادات</Button>
            </Link>
        </div>
    );
}

function StatTile({ icon: Icon, label, value }: { icon: typeof Users; label: string; value: string }) {
    return (
        <div className="rounded-xl border border-border bg-card p-5">
            <div className="flex items-center justify-between">
                <p className="text-sm text-muted-foreground">{label}</p>
                <span className="rounded-lg bg-muted p-1.5 text-muted-foreground">
                    <Icon className="size-4" />
                </span>
            </div>
            <p className="mt-2 font-display text-2xl font-bold tabular-nums">{value}</p>
        </div>
    );
}

function VisitsChart({ series }: { series: Array<{ date: string; visits: number }> }) {
    if (series.length === 0) {
        return <p className="py-8 text-center text-sm text-muted-foreground">لا توجد بيانات لهذه الفترة.</p>;
    }

    const max = Math.max(...series.map((p) => p.visits), 1);

    return (
        <div className="flex h-40 items-end gap-0.5" role="img" aria-label="مخطط الزيارات اليومية">
            {series.map((point) => (
                <div key={point.date} className="group relative flex flex-1 items-end" title={`${point.date}: ${point.visits}`}>
                    <div
                        className="w-full rounded-t bg-druze-blue/80 transition-colors group-hover:bg-druze-blue"
                        style={{ height: `${Math.max((point.visits / max) * 100, 1)}%` }}
                    />
                </div>
            ))}
        </div>
    );
}

function RankList({ title, rows }: { title: string; rows: Row[] }) {
    const max = Math.max(...rows.map((r) => r.value), 1);

    return (
        <section className="rounded-xl border border-border bg-card p-5">
            <h2 className="mb-4 font-display text-lg font-bold">{title}</h2>
            {rows.length === 0 ? (
                <p className="py-6 text-center text-sm text-muted-foreground">لا توجد بيانات.</p>
            ) : (
                <ul className="space-y-2.5">
                    {rows.map((row, i) => (
                        <li key={`${row.label}-${i}`} className="space-y-1">
                            <div className="flex items-center justify-between gap-3 text-sm">
                                <span className="truncate text-foreground" dir="auto">
                                    {row.label}
                                </span>
                                <span className="shrink-0 tabular-nums text-muted-foreground">
                                    {numberFmt.format(row.value)}
                                </span>
                            </div>
                            <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-druze-blue"
                                    style={{ width: `${(row.value / max) * 100}%` }}
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
