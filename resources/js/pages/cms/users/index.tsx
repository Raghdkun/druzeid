import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import * as users from '@/routes/cms/users';
import type { BreadcrumbItem } from '@/types';

interface UserRow {
    id: number;
    name: string;
    email: string;
    role: string;
    createdAt: string | null;
    isSelf: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'المستخدمون', href: '/cms/users' },
];

const ROLE_LABEL: Record<string, string> = { admin: 'مدير', editor: 'محرر' };

export default function UsersIndex({ users: rows, roles }: { users: UserRow[]; roles: string[] }) {
    const [showForm, setShowForm] = useState(false);
    const form = useForm({
        name: '',
        email: '',
        role: 'editor',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(users.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setShowForm(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="المستخدمون" />
            <div className="mx-auto max-w-3xl space-y-4 p-4 sm:p-6">
                <div className="flex items-center justify-between">
                    <h1 className="font-display text-2xl font-bold">المستخدمون</h1>
                    <Button onClick={() => setShowForm((v) => !v)}>
                        <Plus className="size-4" /> مستخدم جديد
                    </Button>
                </div>

                {showForm && (
                    <form
                        onSubmit={submit}
                        className="grid gap-3 rounded-xl border border-border bg-card p-5 sm:grid-cols-2"
                    >
                        <Field label="الاسم" error={form.errors.name}>
                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        </Field>
                        <Field label="البريد الإلكتروني" error={form.errors.email}>
                            <Input dir="ltr" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                        </Field>
                        <Field label="الدور">
                            <select
                                value={form.data.role}
                                onChange={(e) => form.setData('role', e.target.value)}
                                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                {roles.map((role) => (
                                    <option key={role} value={role}>
                                        {ROLE_LABEL[role] ?? role}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="كلمة المرور" error={form.errors.password}>
                            <Input type="password" dir="ltr" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
                        </Field>
                        <Field label="تأكيد كلمة المرور">
                            <Input type="password" dir="ltr" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} />
                        </Field>
                        <div className="flex items-end">
                            <Button type="submit" disabled={form.processing}>إنشاء</Button>
                        </div>
                    </form>
                )}

                <div className="overflow-x-auto rounded-xl border border-border bg-card">
                    <table className="w-full min-w-[28rem] text-sm">
                        <thead className="border-b border-border bg-muted/40">
                            <tr>
                                <th className="px-4 py-2.5 text-start text-xs font-semibold text-muted-foreground">الاسم</th>
                                <th className="px-4 py-2.5 text-start text-xs font-semibold text-muted-foreground">الدور</th>
                                <th className="px-4 py-2.5 text-end text-xs font-semibold text-muted-foreground">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {rows.map((user) => (
                                <tr key={user.id}>
                                    <td className="px-4 py-3">
                                        <div className="font-medium">{user.name}</div>
                                        <div className="text-xs text-muted-foreground" dir="ltr">{user.email}</div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="rounded-full bg-muted px-2 py-0.5 text-xs">
                                            {ROLE_LABEL[user.role] ?? user.role}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-end">
                                        {!user.isSelf && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() => {
                                                    if (confirm(`حذف المستخدم ${user.name}؟`)) {
                                                        router.delete(users.destroy(user.id).url, { preserveScroll: true });
                                                    }
                                                }}
                                                aria-label="حذف"
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        )}
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

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            {children}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
