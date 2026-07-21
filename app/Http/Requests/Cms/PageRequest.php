<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use App\Models\Page;
use App\Models\PageSection;
use App\Support\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-content') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routePage = $this->route('page');
        $pageId = $routePage instanceof Page ? $routePage->id : null;

        return [
            'slug' => [
                'required', 'string', 'max:120',
                'regex:/^[\p{L}\p{N}][\p{L}\p{N}\-_]*$/u',
                Rule::unique('pages', 'slug')->ignore($pageId),
            ],
            'template' => ['required', 'string', 'max:40'],
            'accent' => ['nullable', Rule::in(['blue', 'green', 'red', 'yellow', 'white'])],
            'status' => ['required', Rule::in([Page::STATUS_DRAFT, Page::STATUS_PUBLISHED])],
            'heroMediaId' => ['nullable', 'integer', 'exists:media,id'],
            'sort' => ['nullable', 'integer', 'min:0'],

            // Translatable fields: an object keyed by supported locale codes.
            // The default locale's title is required — a page must have a name.
            'title' => ['required', 'array'],
            'title.'.Locale::default() => ['required', 'string', 'max:200'],
            'title.*' => ['nullable', 'string', 'max:200'],
            'excerpt' => ['nullable', 'array'],
            'excerpt.*' => ['nullable', 'string', 'max:500'],
            'seoTitle' => ['nullable', 'array'],
            'seoTitle.*' => ['nullable', 'string', 'max:200'],
            'seoDescription' => ['nullable', 'array'],
            'seoDescription.*' => ['nullable', 'string', 'max:300'],

            // Sections — each a known type with a JSON payload.
            'sections' => ['present', 'array'],
            'sections.*.type' => ['required', Rule::in(PageSection::TYPES)],
            'sections.*.payload' => ['required', 'array'],
            'sections.*.isVisible' => ['boolean'],
        ] + $this->rejectUnknownLocales();
    }

    /**
     * Guard each translatable object so an unknown locale key cannot slip in.
     *
     * @return array<string, mixed>
     */
    private function rejectUnknownLocales(): array
    {
        $rules = [];

        foreach (['title', 'excerpt', 'seoTitle', 'seoDescription'] as $field) {
            foreach (array_keys((array) $this->input($field, [])) as $key) {
                if (! Locale::isSupported((string) $key)) {
                    $rules["{$field}.{$key}"] = ['prohibited'];
                }
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title.'.Locale::default() => __('Arabic title'),
        ];
    }
}
