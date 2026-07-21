import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import { CompletenessBadges  } from '@/components/cms/locale-badges';
import type {LocaleMeta} from '@/components/cms/locale-badges';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import * as pages from '@/routes/cms/pages';
import type { BreadcrumbItem } from '@/types';

interface PageRow {
    id: number;
    slug: string;
    title: string;
    template: string;
    accent: string | null;
    status: string;
    sectionCount: number;
    updatedAt: string | null;
    completeness: Record<string, number>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'الصفحات', href: '/cms/pages' },
];

export default function PagesIndex({
    pages: rows,
    locales,
}: {
    pages: PageRow[];
    locales: LocaleMeta[];
}) {
    const remove = (page: PageRow) => {
        if (confirm(`حذف صفحة «${page.title}»؟ لا يمكن التراجع عن هذا الإجراء.`)) {
            router.delete(pages.destroy(page.id).url, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="الصفحات" />

            <div className="space-y-4 p-4 sm:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-bold">الصفحات</h1>
                        <p className="text-sm text-muted-foreground">{rows.length} صفحة</p>
                    </div>
                    <Button asChild>
                        <Link href={pages.create().url}>
                            <Plus className="size-4" />
                            صفحة جديدة
                        </Link>
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-xl border border-border bg-card">
                    <table className="w-full min-w-[36rem] text-sm">
                        <thead className="border-b border-border bg-muted/40 text-start">
                            <tr className="text-start">
                                <Th>العنوان</Th>
                                <Th>القالب</Th>
                                <Th>الحالة</Th>
                                <Th>الترجمة</Th>
                                <Th className="text-end">إجراءات</Th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {rows.map((page) => (
                                <tr key={page.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3">
                                        <Link
                                            href={pages.edit(page.id).url}
                                            className="font-medium hover:text-druze-blue"
                                        >
                                            {page.title}
                                        </Link>
                                        <div className="text-xs text-muted-foreground" dir="ltr">
                                            /{page.slug} · {page.sectionCount} أقسام
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">{page.template}</td>
                                    <td className="px-4 py-3">
                                        <StatusPill status={page.status} />
                                    </td>
                                    <td className="px-4 py-3">
                                        <CompletenessBadges
                                            completeness={page.completeness}
                                            locales={locales}
                                        />
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center justify-end gap-1">
                                            <Button variant="ghost" size="icon" asChild>
                                                <Link href={pages.edit(page.id).url} aria-label="تحرير">
                                                    <Pencil className="size-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => remove(page)}
                                                aria-label="حذف"
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

function Th({ children, className }: { children: React.ReactNode; className?: string }) {
    return (
        <th className={`px-4 py-2.5 text-start text-xs font-semibold text-muted-foreground ${className ?? ''}`}>
            {children}
        </th>
    );
}

function StatusPill({ status }: { status: string }) {
    const published = status === 'published';

    return (
        <span
            className={
                published
                    ? 'rounded-full bg-[color-mix(in_srgb,var(--color-druze-green)_15%,transparent)] px-2 py-0.5 text-xs font-medium text-druze-green'
                    : 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
            }
        >
            {published ? 'منشورة' : 'مسودة'}
        </span>
    );
}
