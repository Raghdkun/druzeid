import { Head, router } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import { TranslatableField } from '@/components/cms/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

const newItem = (): MenuItemNode => ({ label: {}, pageId: null, url: null, opensNewTab: false, children: [] });

type Destination = 'page' | 'link' | 'dropdown';

// A `url` of null means "no external link"; an empty string means "external
// link, not yet typed" — so link mode stays selectable while the field is blank.
const destinationOf = (item: MenuItemNode): Destination =>
    item.pageId != null ? 'page' : item.url !== null ? 'link' : 'dropdown';

/** Empty external URLs become null before saving (so validation passes). */
const cleanItems = (items: MenuItemNode[]): MenuItemNode[] =>
    items.map((item) => ({
        ...item,
        url: item.url ? item.url : null,
        children: cleanItems(item.children),
    }));

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

function MenuEditor({ menu, pages, locales }: { menu: MenuData; pages: PageOption[]; locales: LocaleMeta[] }) {
    const [items, setItems] = useState<MenuItemNode[]>(menu.items);
    const [saving, setSaving] = useState(false);

    // Patch the item at [i] or the child at [i, j].
    const patchAt = (path: number[], patch: Partial<MenuItemNode>) =>
        setItems((current) =>
            current.map((item, i) => {
                if (i !== path[0]) {
                    return item;
                }

                if (path.length === 1) {
                    return { ...item, ...patch };
                }

                return {
                    ...item,
                    children: item.children.map((child, j) => (j === path[1] ? { ...child, ...patch } : child)),
                };
            }),
        );

    const move = <T,>(list: T[], index: number, dir: -1 | 1): T[] => {
        const target = index + dir;

        if (target < 0 || target >= list.length) {
            return list;
        }

        const next = [...list];
        [next[index], next[target]] = [next[target], next[index]];

        return next;
    };

    const moveItem = (i: number, dir: -1 | 1) => setItems((c) => move(c, i, dir));
    const removeItem = (i: number) => setItems((c) => c.filter((_, k) => k !== i));
    const addItem = () => setItems((c) => [...c, newItem()]);

    const addChild = (i: number) =>
        setItems((c) => c.map((item, k) => (k === i ? { ...item, children: [...item.children, newItem()] } : item)));
    const removeChild = (i: number, j: number) =>
        setItems((c) => c.map((item, k) => (k === i ? { ...item, children: item.children.filter((_, m) => m !== j) } : item)));
    const moveChild = (i: number, j: number, dir: -1 | 1) =>
        setItems((c) => c.map((item, k) => (k === i ? { ...item, children: move(item.children, j, dir) } : item)));

    const save = () => {
        router.put(
            menus.update(menu.id).url,
            // Menu items are a recursive tree; Inertia's payload type can't
            // express that, so serialize past the constraint. The server
            // validates the shape.
            { items: cleanItems(items) } as unknown as Record<string, string>,
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
                    {saving ? 'جارٍ الحفظ…' : 'حفظ'}
                </Button>
            </div>

            <div className="space-y-3">
                {items.map((item, i) => (
                    <div key={i} className="rounded-lg border border-border p-3">
                        <RowChrome
                            title={`عنصر ${i + 1}`}
                            index={i}
                            total={items.length}
                            onMove={(dir) => moveItem(i, dir)}
                            onRemove={() => removeItem(i)}
                        />
                        <ItemFields item={item} pages={pages} locales={locales} onChange={(patch) => patchAt([i], patch)} />

                        {item.children.length > 0 && (
                            <div className="mt-3 space-y-3 border-s-2 border-hairline ps-4">
                                {item.children.map((child, j) => (
                                    <div key={j} className="rounded-md border border-border/70 p-2.5">
                                        <RowChrome
                                            title={`فرعي ${j + 1}`}
                                            index={j}
                                            total={item.children.length}
                                            onMove={(dir) => moveChild(i, j, dir)}
                                            onRemove={() => removeChild(i, j)}
                                        />
                                        <ItemFields item={child} pages={pages} locales={locales} onChange={(patch) => patchAt([i, j], patch)} />
                                    </div>
                                ))}
                            </div>
                        )}

                        <button
                            type="button"
                            onClick={() => addChild(i)}
                            className="mt-3 inline-flex items-center gap-1 text-xs font-medium text-druze-blue hover:underline"
                        >
                            <Plus className="size-3.5" /> إضافة عنصر فرعي
                        </button>
                    </div>
                ))}
            </div>

            <Button type="button" variant="outline" size="sm" className="mt-4" onClick={addItem}>
                <Plus className="size-4" /> إضافة عنصر
            </Button>
        </section>
    );
}

function RowChrome({
    title,
    index,
    total,
    onMove,
    onRemove,
}: {
    title: string;
    index: number;
    total: number;
    onMove: (dir: -1 | 1) => void;
    onRemove: () => void;
}) {
    return (
        <div className="mb-2 flex items-center gap-2">
            <span className="text-xs font-semibold text-muted-foreground">{title}</span>
            <div className="ms-auto flex items-center gap-1">
                <button type="button" disabled={index === 0} onClick={() => onMove(-1)} aria-label="نقل لأعلى" className="text-muted-foreground disabled:opacity-30">
                    <ChevronUp className="size-4" />
                </button>
                <button type="button" disabled={index === total - 1} onClick={() => onMove(1)} aria-label="نقل لأسفل" className="text-muted-foreground disabled:opacity-30">
                    <ChevronDown className="size-4" />
                </button>
                <button type="button" onClick={onRemove} aria-label="حذف" className="text-destructive">
                    <Trash2 className="size-4" />
                </button>
            </div>
        </div>
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
    const destination = destinationOf(item);

    const setDestination = (value: Destination) => {
        if (value === 'page') {
            onChange({ pageId: item.pageId ?? null, url: null });
        } else if (value === 'link') {
            onChange({ pageId: null, url: item.url ?? '' });
        } else {
            onChange({ pageId: null, url: null });
        }
    };

    return (
        <div className="grid gap-3 sm:grid-cols-2">
            <TranslatableField label="التسمية" value={item.label} locales={locales} onChange={(label) => onChange({ label })} />

            <div className="space-y-2">
                <div className="space-y-1.5">
                    <Label>الوجهة</Label>
                    <select
                        value={destination}
                        onChange={(e) => setDestination(e.target.value as Destination)}
                        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="page">صفحة داخلية</option>
                        <option value="link">رابط خارجي</option>
                        <option value="dropdown">قائمة منسدلة (بدون رابط)</option>
                    </select>
                </div>

                {destination === 'page' && (
                    <select
                        value={item.pageId ?? ''}
                        onChange={(e) => onChange({ pageId: e.target.value ? Number(e.target.value) : null })}
                        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">— اختر صفحة —</option>
                        {pages.map((page) => (
                            <option key={page.id} value={page.id}>
                                {page.title ?? page.slug}
                            </option>
                        ))}
                    </select>
                )}

                {destination === 'link' && (
                    <Input
                        dir="ltr"
                        placeholder="https://example.com"
                        value={item.url ?? ''}
                        onChange={(e) => onChange({ url: e.target.value })}
                    />
                )}

                {destination !== 'dropdown' && (
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            className="size-4"
                            checked={item.opensNewTab}
                            onChange={(e) => onChange({ opensNewTab: e.target.checked })}
                        />
                        فتح في نافذة جديدة
                    </label>
                )}
            </div>
        </div>
    );
}
