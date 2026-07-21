import { Head, router } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import { TranslatableField } from '@/components/cms/translatable-field';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import * as menus from '@/routes/cms/menus';
import type { BreadcrumbItem } from '@/types';

interface MenuItemNode {
    id?: number;
    label: Record<string, string>;
    pageId: number | null;
    url: string | null;
    opensNewTab: boolean;
    children: MenuItemNode[];
}

interface MenuData {
    id: number;
    key: string;
    name: string;
    items: MenuItemNode[];
}

interface PageOption {
    id: number;
    slug: string;
    title: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'القوائم', href: '/cms/menus' },
];

const MENU_LABELS: Record<string, string> = {
    primary: 'القائمة الرئيسية',
    footer_sections: 'تذييل — أقسام الموقع',
    footer_organization: 'تذييل — المنظمة',
};

export default function MenusIndex({
    menus: menuList,
    pages,
    locales,
}: {
    menus: MenuData[];
    pages: PageOption[];
    locales: LocaleMeta[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="القوائم" />
            <div className="space-y-6 p-4 sm:p-6">
                <h1 className="font-display text-2xl font-bold">القوائم</h1>
                {menuList.map((menu) => (
                    <MenuEditor key={menu.id} menu={menu} pages={pages} locales={locales} />
                ))}
            </div>
        </AppLayout>
    );
}

function MenuEditor({
    menu,
    pages,
    locales,
}: {
    menu: MenuData;
    pages: PageOption[];
    locales: LocaleMeta[];
}) {
    const [items, setItems] = useState<MenuItemNode[]>(menu.items);
    const [saving, setSaving] = useState(false);

    const updateItem = (path: number[], patch: Partial<MenuItemNode>) => {
        const next = structuredClone(items);
        const node = path.length === 1 ? next[path[0]] : next[path[0]].children[path[1]];
        Object.assign(node, patch);
        setItems(next);
    };

    const save = () => {
        router.put(
            menus.update(menu.id).url,
            // Menu items are a recursive tree; Inertia's payload type can't
            // express that, so serialize past the constraint. The server
            // validates the shape.
            { items } as unknown as Record<string, string>,
            {
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <section className="rounded-xl border border-border bg-card p-5">
            <div className="mb-4 flex items-center justify-between">
                <h2 className="font-display text-lg font-bold">{MENU_LABELS[menu.key] ?? menu.name}</h2>
                <Button size="sm" onClick={save} disabled={saving}>
                    <Save className="size-4" />
                    حفظ
                </Button>
            </div>

            <div className="space-y-4">
                {items.map((item, i) => (
                    <div key={i} className="rounded-lg border border-border p-3">
                        <ItemFields
                            item={item}
                            pages={pages}
                            locales={locales}
                            onChange={(patch) => updateItem([i], patch)}
                        />
                        {item.children.length > 0 && (
                            <div className="mt-3 space-y-3 border-s-2 border-hairline ps-4">
                                {item.children.map((child, j) => (
                                    <ItemFields
                                        key={j}
                                        item={child}
                                        pages={pages}
                                        locales={locales}
                                        onChange={(patch) => updateItem([i, j], patch)}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </section>
    );
}

function ItemFields({
    item,
    pages,
    locales,
    onChange,
}: {
    item: MenuItemNode;
    pages: PageOption[];
    locales: LocaleMeta[];
    onChange: (patch: Partial<MenuItemNode>) => void;
}) {
    return (
        <div className="grid gap-3 sm:grid-cols-2">
            <TranslatableField
                label="التسمية"
                value={item.label}
                locales={locales}
                onChange={(label) => onChange({ label })}
            />
            <div className="space-y-1.5">
                <label className="text-sm font-medium">الوجهة</label>
                <select
                    value={item.pageId ?? ''}
                    onChange={(e) =>
                        onChange({ pageId: e.target.value ? Number(e.target.value) : null })
                    }
                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">— قائمة منسدلة (بدون رابط) —</option>
                    {pages.map((page) => (
                        <option key={page.id} value={page.id}>
                            {page.title ?? page.slug}
                        </option>
                    ))}
                </select>
            </div>
        </div>
    );
}
