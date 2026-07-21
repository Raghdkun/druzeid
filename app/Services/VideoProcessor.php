<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Reads a stored video's dimensions/duration and extracts a poster frame, via
 * ffmpeg/ffprobe. Everything degrades gracefully: when the binaries are missing
 * (or the disk is not local) the video is still usable — it just uploads
 * without stored dimensions or a poster, and the player falls back to the
 * first frame.
 *
 * ffmpeg is invoked through the Process facade with an argument array, so the
 * command never touches a shell and no path can inject arguments.
 */
class VideoProcessor
{
    /**
     * @return array{width: ?int, height: ?int, duration: ?int, posterPath: ?string}
     */
    public function process(string $path, string $disk = 'public'): array
    {
        $blank = ['width' => null, 'height' => null, 'duration' => null, 'posterPath' => null];

        try {
            $storage = Storage::disk($disk);
            // path() only exists for local disks; a cloud disk throws and we
            // skip processing rather than downloading the whole clip.
            $absolute = $storage->path($path);
        } catch (Throwable) {
            return $blank;
        }

        if (! is_file($absolute)) {
            return $blank;
        }

        $probe = $this->probe($absolute);

        return [
            ...$probe,
            'posterPath' => $this->poster($path, $absolute, $storage, $probe['width']),
        ];
    }

    /**
     * @return array{width: ?int, height: ?int, duration: ?int}
     */
    private function probe(string $absolute): array
    {
        $blank = ['width' => null, 'height' => null, 'duration' => null];

        try {
            $result = Process::timeout(60)->run([
                (string) config('media.ffprobe'),
                '-v', 'error',
                '-select_streams', 'v:0',
                '-show_entries', 'stream=width,height',
                '-show_entries', 'format=duration',
                '-of', 'json',
                $absolute,
            ]);
        } catch (Throwable) {
            return $blank;
        }

        if (! $result->successful()) {
            return $blank;
        }

        $data = json_decode($result->output(), true);

        if (! is_array($data)) {
            return $blank;
        }

        $stream = $data['streams'][0] ?? [];
        $duration = $data['format']['duration'] ?? null;

        return [
            'width' => isset($stream['width']) ? (int) $stream['width'] : null,
            'height' => isset($stream['height']) ? (int) $stream['height'] : null,
            'duration' => is_numeric($duration) ? (int) round((float) $duration) : null,
        ];
    }

    /**
     * Grab a single frame as the poster and store it next to the video.
     * Returns the storage-relative path, or null on any failure.
     */
    private function poster(string $path, string $absolute, Filesystem $storage, ?int $width): ?string
    {
        $posterPath = $this->posterPathFor($path);
        $posterAbsolute = $storage->path($posterPath);
        $target = min(1280, $width ?: 1280);

        try {
            $result = Process::timeout(120)->run([
                (string) config('media.ffmpeg'),
                '-y',
                '-ss', (string) config('media.poster_at', 1.0),
                '-i', $absolute,
                '-frames:v', '1',
                // Even width keeps the -2 height divisible by 2 (yuv constraint).
                '-vf', 'scale='.($target - ($target % 2)).':-2',
                '-q:v', '3',
                $posterAbsolute,
            ]);
        } catch (Throwable) {
            return null;
        }

        return $result->successful() && is_file($posterAbsolute) ? $posterPath : null;
    }

    private function posterPathFor(string $path): string
    {
        $dot = strrpos($path, '.');
        $base = $dot === false ? $path : substr($path, 0, $dot);

        return $base.'-poster.jpg';
    }
}
