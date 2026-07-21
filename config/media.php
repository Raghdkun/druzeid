<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Upload limits (kilobytes)
    |--------------------------------------------------------------------------
    |
    | Images stay small; video is allowed to be larger. The hard cap is the
    | video limit — the image sub-limit is enforced after the MIME type is
    | known. Both must sit under PHP's upload_max_filesize / post_max_size.
    |
    */

    'image_max_kb' => (int) env('MEDIA_IMAGE_MAX_KB', 8192),      // 8 MB
    'video_max_kb' => (int) env('MEDIA_VIDEO_MAX_KB', 102400),    // 100 MB

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME types
    |--------------------------------------------------------------------------
    |
    | Validated against the file's *detected* MIME (via finfo), not its
    | extension, so a renamed payload cannot slip through. SVG is deliberately
    | NOT accepted for upload: it is markup, can carry script, and is served
    | straight from /storage on the app origin — a stored-XSS vector. (Trusted
    | brand SVGs are seeded assets, not CMS uploads.)
    |
    */

    'image_mimes' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ],

    'video_mimes' => [
        'video/mp4',
        'video/webm',
    ],

    /*
    |--------------------------------------------------------------------------
    | FFmpeg
    |--------------------------------------------------------------------------
    |
    | Used to probe video dimensions/duration and extract a poster frame. When
    | the binaries are absent the pipeline degrades gracefully: videos upload
    | without a poster and without stored dimensions. Set absolute paths in
    | production; leave the bare command to resolve from PATH in development.
    |
    */

    'ffmpeg' => env('FFMPEG_PATH', 'ffmpeg'),
    'ffprobe' => env('FFPROBE_PATH', 'ffprobe'),

    // Seconds into the clip to grab the poster frame from.
    'poster_at' => (float) env('MEDIA_POSTER_AT', 1.0),
];
