import { Head, router } from '@inertiajs/react';
import { AlertTriangle, Database, Download, Loader2, Lock, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Backup {
    name: string;
    size: number;
    createdAt: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'النسخ الاحتياطي', href: '/cms/backups' },
];

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let i = 0;

    while (value >= 1024 && i < units.length - 1) {
        value /= 1024;
        i++;
    }

    return `${value.toFixed(1)} ${units[i]}`;
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString('ar-EG', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export default function Backups({
    backups,
    encrypted,
    keep,
}: {
    backups: Backup[];
    encrypted: boolean;
    keep: number;
}) {
    const [running, setRunning] = useState(false);

    const runBackup = () => {
        router.post(
            '/cms/backups',
            {},
            {
                preserveScroll: true,
                onStart: () => setRunning(true),
                onFinish: () => setRunning(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="النسخ الاحتياطي" />

            <div className="mx-auto max-w-4xl space-y-5 p-4 sm:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="font-display text-2xl font-bold">النسخ الاحتياطي</h1>
                    <Button onClick={runBackup} disabled={running}>
                        {running ? <Loader2 className="size-4 animate-spin" /> : <Database className="size-4" />}
                        {running ? 'جارٍ الإنشاء…' : 'إنشاء نسخة الآن'}
                    </Button>
                </div>

                <p className="text-sm text-muted-foreground">
                    تشمل كل نسخة قاعدة البيانات والوسائط المرفوعة، وتُحفظ في مكان محمي لا يمكن
                    الوصول إليه من الويب. يُحتفظ بآخر {keep} نسخ فقط، وتُنشأ نسخة تلقائياً كل أسبوع.
                </p>

                {encrypted ? (
                    <div className="flex items-center gap-2 rounded-lg border border-[color-mix(in_srgb,var(--color-druze-green)_35%,transparent)] bg-[color-mix(in_srgb,var(--color-druze-green)_10%,transparent)] p-3 text-sm text-druze-green">
                        <Lock className="size-4 shrink-0" />
                        النسخ مشفّرة (AES-256). يلزم كلمة المرور لفتحها أو استعادتها.
                    </div>
                ) : (
                    <div className="flex items-start gap-2 rounded-lg border border-[color-mix(in_srgb,var(--color-druze-yellow)_40%,transparent)] bg-[color-mix(in_srgb,var(--color-druze-yellow)_10%,transparent)] p-3 text-sm">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0 text-[color-mix(in_srgb,var(--color-druze-yellow)_70%,black)]" />
                        <span>
                            النسخ غير مشفّرة. اضبط المتغيّر <code className="rounded bg-muted px-1 font-mono text-xs">BACKUP_ARCHIVE_PASSWORD</code>{' '}
                            في إعدادات الخادم لتشفير الأرشيف بكلمة مرور.
                        </span>
                    </div>
                )}

                <div className="overflow-x-auto rounded-xl border border-border bg-card">
                    <table className="w-full min-w-[32rem] text-sm">
                        <thead className="border-b border-border bg-muted/40">
                            <tr>
                                <th className="px-4 py-2.5 text-start text-xs font-semibold text-muted-foreground">النسخة</th>
                                <th className="px-4 py-2.5 text-start text-xs font-semibold text-muted-foreground">الحجم</th>
                                <th className="px-4 py-2.5 text-end text-xs font-semibold text-muted-foreground">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {backups.map((backup) => (
                                <tr key={backup.name}>
                                    <td className="px-4 py-3">
                                        <div className="font-medium" dir="ltr">{backup.name}</div>
                                        <div className="text-xs text-muted-foreground">{formatDate(backup.createdAt)}</div>
                                    </td>
                                    <td className="px-4 py-3 tabular-nums text-muted-foreground">{formatSize(backup.size)}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center justify-end gap-1">
                                            <a href={`/cms/backups/${backup.name}/download`}>
                                                <Button variant="ghost" size="icon" aria-label="تنزيل">
                                                    <Download className="size-4" />
                                                </Button>
                                            </a>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive hover:text-destructive"
                                                aria-label="حذف"
                                                onClick={() => {
                                                    if (confirm('حذف هذه النسخة الاحتياطية؟')) {
                                                        router.delete(`/cms/backups/${backup.name}`, { preserveScroll: true });
                                                    }
                                                }}
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {backups.length === 0 && (
                        <p className="py-12 text-center text-sm text-muted-foreground">
                            لا توجد نسخ احتياطية بعد. أنشئ واحدة الآن.
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
