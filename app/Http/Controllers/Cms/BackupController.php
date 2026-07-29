<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Services\Backup\BackupService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Database backups. Admin-only (behind `can:manage-settings`). Archives live on
 * a private disk and are streamed through the download action — never served
 * from a public URL.
 */
class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    public function index(): Response
    {
        return inertia('cms/backups/index', [
            'backups' => $this->backups->list(),
            'encrypted' => $this->backups->encrypted(),
            'keep' => (int) config('backup.keep'),
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $this->backups->create(now()->format('Y-m-d_His'));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', __('The backup could not be created.'));
        }

        return back()->with('success', __('Backup created.'));
    }

    public function download(string $name): BinaryFileResponse
    {
        $path = $this->backups->path($name);

        abort_if($path === null, 404);

        return response()->download($path, $name, ['Content-Type' => 'application/zip']);
    }

    public function destroy(string $name): RedirectResponse
    {
        $this->backups->delete($name);

        return back()->with('success', __('Backup deleted.'));
    }
}
