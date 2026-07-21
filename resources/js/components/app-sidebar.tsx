import { Link, usePage } from '@inertiajs/react';
import { FileText, Home, Images, LayoutGrid, ListTree, Settings, Users } from 'lucide-react';

import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { DruzeStar } from '@/components/site/druze-star';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useI18n } from '@/hooks/use-i18n';
import { overview } from '@/routes/cms';
import * as media from '@/routes/cms/media';
import * as menus from '@/routes/cms/menus';
import * as pages from '@/routes/cms/pages';
import * as settings from '@/routes/cms/settings';
import * as users from '@/routes/cms/users';
import type { NavItem } from '@/types';

/**
 * CMS sidebar. Renders on the inline-start edge — the physical right in the
 * Arabic-default admin — by passing `side="right"` when the document is RTL.
 * Nav items are gated to the signed-in user's role: everyone sees content,
 * only admins see settings and users.
 */
export function AppSidebar() {
    const { isRtl } = useI18n();
    const { auth } = usePage().props;
    const isAdmin = auth.user?.role === 'admin';

    const contentNav: NavItem[] = [
        { title: 'لوحة التحكم', href: overview(), icon: LayoutGrid },
        { title: 'الصفحات', href: pages.index(), icon: FileText },
        { title: 'الوسائط', href: media.index(), icon: Images },
        { title: 'القوائم', href: menus.index(), icon: ListTree },
    ];

    const adminNav: NavItem[] = [
        { title: 'الإعدادات', href: settings.edit(), icon: Settings },
        { title: 'المستخدمون', href: users.index(), icon: Users },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset" side={isRtl ? 'right' : 'left'}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={overview()} prefetch>
                                <DruzeStar className="size-8 shrink-0" decorative />
                                <span className="font-display text-base font-bold">
                                    الهوية الدرزية
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={contentNav} />
                {isAdmin && <NavMain items={adminNav} label="الإدارة" />}
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild>
                            <a href="/" target="_blank" rel="noreferrer">
                                <Home />
                                <span>عرض الموقع</span>
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
