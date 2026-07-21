import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title="نسيت كلمة المرور" />

            {status && (
                <div
                    role="status"
                    className="mb-6 rounded-md bg-[color-mix(in_srgb,var(--color-druze-green)_10%,white)] px-4 py-3 text-center text-sm font-medium text-druze-green"
                >
                    {status}
                </div>
            )}

            <Form {...email.form()} className="flex flex-col gap-5">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-1.5">
                            <Label htmlFor="email">البريد الإلكتروني</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                dir="ltr"
                                autoComplete="off"
                                autoFocus
                                placeholder="name@druze.id"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <Button
                            size="lg"
                            className="w-full"
                            disabled={processing}
                            data-test="email-password-reset-link-button"
                        >
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            إرسال رابط إعادة التعيين
                        </Button>
                    </>
                )}
            </Form>

            <p className="mt-6 text-center text-sm text-ink-muted">
                العودة إلى <TextLink href={login()}>تسجيل الدخول</TextLink>
            </p>
        </>
    );
}

ForgotPassword.layout = {
    title: 'نسيت كلمة المرور',
    description: 'أدخل بريدك الإلكتروني لتصلك رسالة بإعادة تعيين كلمة المرور',
};
