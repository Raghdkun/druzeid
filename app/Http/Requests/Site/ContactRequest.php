<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            // rfc only — no DNS lookup. A public form must not reject a valid
            // address because the sender's mail host has quirky DNS, and this
            // keeps validation network-independent and testable offline.
            'email' => ['required', 'email:rfc', 'max:190'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],

            // Honeypot — hidden from real users, so any value means a bot.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('contact.name'),
            'email' => __('contact.email'),
            'subject' => __('contact.subject'),
            'message' => __('contact.message'),
        ];
    }

    /**
     * Reject submissions completed implausibly fast — the form stamps its
     * render time and a genuine visitor cannot type a 10-character message
     * in under three seconds.
     *
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $renderedAt = (int) $this->input('rendered_at', 0);
                $elapsed = now()->getTimestamp() - $renderedAt;

                if ($renderedAt > 0 && $elapsed < 3) {
                    $validator->errors()->add('message', __('contact.error'));
                }
            },
        ];
    }
}
