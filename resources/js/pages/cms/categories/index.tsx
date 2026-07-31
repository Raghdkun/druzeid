import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import * as categories from '@/routes/cms/categories';
import type { BreadcrumbItem } from '@/types';

interface CategoryRow {
    id: number;
    name: string;
    slug: string;
    accent: string | null;
    articlesCount: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'التصنيفات', href: '/cms/categories' },
];

export default function CategoriesIndex({ categories: rows }: { categories: CategoryRow[] }) {
    const remove = (category: CategoryRow) => {
        if (confirm(`حذف التصنيف «${category.name}»؟`)) {
            router.delete(categories.destroy(category.id).url, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="التصنيفات" />

            <div className="space-y-4 p-4 sm:p-6">
                <div className="flex items-center justify-between">
                    <h1 className="font-display text-2xl font-bold">التصنيفات</h1>
                    <Button asChild>
                        <Link href={categories.create().url}>
                            <Plus className="size-4" /> تصنيف جديد
                        </Link>
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-xl border border-border bg-card">
                    <table className="w-full min-w-[32rem] text-sm">
                        <thead className="border-b border-border bg-muted/40">
                            <tr>
                                <Th>الاسم</Th>
                                <Th>المسار</Th>
                                <Th>عدد الأخبار</Th>
                                <Th className="text-end">إجراءات</Th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {rows.map((category) => (
                                <tr key={category.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3">
                                        <Link
                                            href={categories.edit(category.id).url}
                                            className="font-medium hover:text-druze-blue"
                                        >
                                            {category.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground" dir="ltr">{category.slug}</td>
                                    <td className="px-4 py-3 text-muted-foreground">{category.articlesCount}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center justify-end gap-1">
                                            <Button variant="ghost" size="icon" asChild>
                                                <Link href={categories.edit(category.id).url} aria-label="تحرير">
                                                    <Pencil className="size-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => remove(category)}
                                                aria-label="حذف"
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {rows.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-16 text-center text-muted-foreground">
                                        لا توجد تصنيفات بعد.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

function Th({ children, className }: { children: React.ReactNode; className?: string }) {
    return (
        <th className={cn('px-4 py-2.5 text-start text-xs font-semibold text-muted-foreground', className)}>
            {children}
        </th>
    );
}
