import { Form, Head } from '@inertiajs/react';

import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    return (
        <>
            <Head title="إعادة تعيين كلمة المرور" />

            <Form
                {...update.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-1.5">
                            <Label htmlFor="email">البريد الإلكتروني</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                dir="ltr"
                                autoComplete="email"
                                value={email}
                                readOnly
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="password">كلمة المرور الجديدة</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                dir="ltr"
                                autoComplete="new-password"
                                autoFocus
                                placeholder="••••••••"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="password_confirmation">تأكيد كلمة المرور</Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                dir="ltr"
                                autoComplete="new-password"
                                placeholder="••••••••"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>

                        <Button
                            type="submit"
                            size="lg"
                            className="mt-1 w-full"
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing && <Spinner />}
                            حفظ كلمة المرور
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'إعادة تعيين كلمة المرور',
    description: 'أدخل كلمة المرور الجديدة أدناه',
};
