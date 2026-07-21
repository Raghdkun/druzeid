<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\ImageVariants;
use App\Services\VideoProcessor;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString();

        $media = Media::query()
            ->when($filter === 'review', fn ($q) => $q->where('needs_alt_review', true))
            ->when($filter === 'video', fn ($q) => $q->where('kind', 'video'))
            ->when($filter === 'image', fn ($q) => $q->where('kind', 'image'))
            ->latest()
            ->paginate(48)
            ->through(fn (Media $medium) => $this->present($medium));

        return inertia('cms/media/index', [
            'media' => $media,
            'filter' => $filter ?: 'all',
            'reviewCount' => Media::where('needs_alt_review', true)->count(),
            'videoCount' => Media::where('kind', 'video')->count(),
            'locales' => $this->localeMeta(),
        ]);
    }

    public function __construct(
        private readonly ImageVariants $variants,
        private readonly VideoProcessor $video,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $allowed = [...config('media.image_mimes'), ...config('media.video_mimes')];

        // Validate against the detected MIME (finfo), not the extension, so a
        // renamed payload cannot pass. The hard size cap is the video limit;
        // the tighter image limit is enforced once the kind is known.
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.(int) config('media.video_max_kb'),
                'mimetypes:'.implode(',', $allowed),
            ],
        ]);

        $file = $request->file('file');
        $realPath = $file?->getRealPath();

        if ($file === null || $realPath === false || $realPath === null) {
            return back()->with('error', __('The upload could not be read.'));
        }

        $mime = (string) $file->getMimeType();
        $isVideo = str_starts_with($mime, 'video/');

        if (! $isVideo && $file->getSize() > (int) config('media.image_max_kb') * 1024) {
            return back()->with('error', __('That image is larger than the :max MB limit.', [
                'max' => (int) round(config('media.image_max_kb') / 1024),
            ]));
        }

        $checksum = hash_file('sha256', $realPath);

        if ($checksum === false) {
            return back()->with('error', __('The upload could not be read.'));
        }

        if (Media::where('checksum', $checksum)->exists()) {
            return back()->with('error', __('That file is already in the library.'));
        }

        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: ($isVideo ? 'video' : 'image');
        $path = $file->storeAs(
            'media',
            substr($checksum, 0, 8).'-'.Str::limit($name, 60, '').'.'.$file->extension(),
            'public'
        );

        if ($path === false) {
            return back()->with('error', __('The upload could not be saved.'));
        }

        if ($isVideo) {
            $probe = $this->video->process($path);
            $attributes = [
                'width' => $probe['width'],
                'height' => $probe['height'],
                'duration' => $probe['duration'],
                'poster_path' => $probe['posterPath'],
                'variants' => [],
                // Videos are described by their caption, not the image alt queue.
                'needs_alt_review' => false,
            ];
        } else {
            [$width, $height] = getimagesize($realPath) ?: [null, null];
            $attributes = [
                'width' => $width,
                'height' => $height,
                'variants' => $this->variants->generate($path),
                'needs_alt_review' => true,
            ];
        }

        Media::create([
            'disk' => 'public',
            'path' => $path,
            'kind' => $isVideo ? 'video' : 'image',
            'filename' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => $file->getSize(),
            'alt' => [],
            'caption' => [],
            'checksum' => $checksum,
            ...$attributes,
        ]);

        return back()->with('success', $isVideo ? __('Video uploaded.') : __('Image uploaded.'));
    }

    public function update(Request $request, Media $medium): RedirectResponse
    {
        $localeKeys = implode(',', Locale::codes());

        $validated = $request->validate([
            'alt' => ['nullable', 'array'],
            'alt.*' => ['nullable', 'string', 'max:300'],
            'caption' => ['nullable', 'array'],
            'caption.*' => ['nullable', 'string', 'max:500'],
            'credit' => ['nullable', 'string', 'max:190'],
            'isDecorative' => ['boolean'],
            'focalX' => ['nullable', 'numeric', 'between:0,1'],
            'focalY' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $medium->setTranslations('alt', $validated['alt'] ?? []);
        $medium->setTranslations('caption', $validated['caption'] ?? []);
        $medium->credit = $validated['credit'] ?? null;
        $medium->is_decorative = $validated['isDecorative'] ?? false;
        $medium->focal_x = $validated['focalX'] ?? $medium->focal_x;
        $medium->focal_y = $validated['focalY'] ?? $medium->focal_y;

        // An image is out of the review queue once it is either described in
        // the default locale or explicitly marked decorative. Video is never in
        // the (image) alt-review queue — it is described by its caption.
        $medium->needs_alt_review = ! $medium->is_video
            && ! $medium->is_decorative
            && ! $medium->hasTranslation('alt', Locale::default());

        $medium->save();

        return back()->with('success', __('Image updated.'));
    }

    public function destroy(Media $medium): RedirectResponse
    {
        // Remove the file, its derivatives and any poster too, so deleting from
        // the library reclaims all the disk it used.
        Storage::disk($medium->disk)->delete(array_filter([$medium->path, $medium->poster_path]));
        $this->variants->delete($medium->variants ?? [], $medium->disk);
        $medium->delete();

        return back()->with('success', __('Image deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Media $medium): array
    {
        return [
            'id' => $medium->id,
            'url' => $medium->url,
            'kind' => $medium->kind,
            'poster' => $medium->poster_url,
            'duration' => $medium->duration,
            'filename' => $medium->filename,
            'width' => $medium->width,
            'height' => $medium->height,
            'mime' => $medium->mime,
            'alt' => $medium->getTranslations('alt'),
            'caption' => $medium->getTranslations('caption'),
            'credit' => $medium->credit,
            'isDecorative' => $medium->is_decorative,
            'needsAltReview' => $medium->needs_alt_review,
            'focalX' => $medium->focal_x,
            'focalY' => $medium->focal_y,
            'usedOnPages' => $medium->usedOnPages(),
        ];
    }

    /**
     * @return list<array{code: string, name: string, dir: string, default: bool}>
     */
    private function localeMeta(): array
    {
        return array_map(fn (string $code) => [
            'code' => $code,
            'name' => Locale::meta($code)['name'],
            'dir' => Locale::dir($code),
            'default' => $code === Locale::default(),
        ], Locale::codes());
    }
}
