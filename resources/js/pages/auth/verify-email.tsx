import { Form, Head } from '@inertiajs/react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <Head title="تأكيد البريد الإلكتروني" />

            {status === 'verification-link-sent' && (
                <div
                    role="status"
                    className="mb-6 rounded-md bg-[color-mix(in_srgb,var(--color-druze-green)_10%,white)] px-4 py-3 text-center text-sm font-medium text-druze-green"
                >
                    تم إرسال رابط تأكيد جديد إلى بريدك الإلكتروني.
                </div>
            )}

            <Form {...send.form()} className="flex flex-col items-center gap-5 text-center">
                {({ processing }) => (
                    <>
                        <Button size="lg" className="w-full" disabled={processing} variant="outline">
                            {processing && <Spinner />}
                            إعادة إرسال رسالة التأكيد
                        </Button>

                        <TextLink href={logout()} className="text-sm">
                            تسجيل الخروج
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'تأكيد البريد الإلكتروني',
    description: 'يرجى تأكيد بريدك الإلكتروني بالضغط على الرابط الذي أرسلناه إليك.',
};
