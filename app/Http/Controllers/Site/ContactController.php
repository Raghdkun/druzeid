<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ContactRequest;
use App\Mail\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(ContactRequest $request): RedirectResponse
    {
        $recipient = Setting::get('contact.email');

        if (filled($recipient)) {
            Mail::to($recipient)->send(new ContactMessage([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'subject' => $request->string('subject')->toString(),
                'message' => $request->string('message')->toString(),
                'locale' => app()->getLocale(),
            ]));
        }

        return back()->with('success', __('contact.success'));
    }
}
