import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {/* min-w-0 stops this content column (a flex item of the main)
                    from being widened to its widest child, so tables and other
                    wide content scroll within their own overflow box instead of
                    overflowing the viewport. */}
                <div className="min-w-0 flex-1">{children}</div>
            </AppContent>
        </AppShell>
    );
}
