import { Form, Head } from '@inertiajs/react';

import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="تسجيل الدخول" />

            {status && (
                <div
                    role="status"
                    className="mb-6 rounded-md bg-[color-mix(in_srgb,var(--color-druze-green)_10%,white)] px-4 py-3 text-center text-sm font-medium text-druze-green"
                >
                    {status}
                </div>
            )}

            <PasskeyVerify
                label="الدخول بمفتاح المرور"
                loadingLabel="جارٍ الدخول…"
                separator="أو تابع بالبريد الإلكتروني"
            />

            <Form {...store.form()} resetOnSuccess={['password']} className="flex flex-col gap-5">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-1.5">
                            <Label htmlFor="email">البريد الإلكتروني</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                dir="ltr"
                                required
                                autoFocus
                                autoComplete="email"
                                placeholder="name@druze.id"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-1.5">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="password">كلمة المرور</Label>
                                {canResetPassword && (
                                    <TextLink href={request()} className="text-sm">
                                        نسيت كلمة المرور؟
                                    </TextLink>
                                )}
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                dir="ltr"
                                required
                                autoComplete="current-password"
                                placeholder="••••••••"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <label className="flex items-center gap-2.5 text-sm text-ink-soft">
                            <Checkbox id="remember" name="remember" />
                            تذكّرني
                        </label>

                        <Button
                            type="submit"
                            size="lg"
                            className="mt-1 w-full"
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            دخول
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'تسجيل الدخول',
    description: 'أدخل بريدك الإلكتروني وكلمة المرور للوصول إلى لوحة التحكم',
};
