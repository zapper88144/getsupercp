<?php

namespace App\Http\Controllers;

use App\Services\FileService;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FileManagerController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(protected FileService $fileService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('FileManager/Index', [
            'initialPath' => "/home/{$request->user()->name}/web",
        ]);
    }

    public function list(Request $request)
    {
        $path = $this->getSafePath($request, $request->get('path'));

        try {
            $response = $this->fileService->listFiles($path);

            return response()->json($response);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to list files.');
        }
    }

    public function read(Request $request)
    {
        $path = $this->getSafePath($request, $request->get('path'));

        try {
            $response = $this->fileService->readFile($path);

            return response()->json(['content' => $response]);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to read file.');
        }
    }

    public function write(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'content' => 'required|string',
        ]);

        $path = $this->getSafePath($request, $request->path);

        try {
            $response = $this->fileService->writeFile($path, $request->content);

            return response()->json(['message' => $response]);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to write file.');
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $this->getSafePath($request, $request->path);

        try {
            $response = $this->fileService->deleteFile($path);

            return response()->json(['message' => $response]);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to delete file.');
        }
    }

    public function createDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $this->getSafePath($request, $request->path);

        try {
            $response = $this->fileService->createDirectory($path);

            return response()->json(['message' => $response]);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to create directory.');
        }
    }

    public function rename(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
        ]);

        $from = $this->getSafePath($request, $request->from);
        $to = $this->getSafePath($request, $request->to);

        try {
            $response = $this->fileService->renameFile($from, $to);

            return response()->json(['message' => $response]);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to rename file.');
        }
    }

    public function upload(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'file' => 'required|file',
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());
        $targetPath = rtrim($request->path, '/').'/'.$file->getClientOriginalName();
        $targetPath = $this->getSafePath($request, $targetPath);

        try {
            $this->fileService->writeFile($targetPath, $content);

            return response()->json(['message' => 'File uploaded successfully']);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to upload file.');
        }
    }

    public function download(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $this->getSafePath($request, $request->path);

        try {
            $response = $this->fileService->readFile($path);
            $filename = basename($path);

            return response($response)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to download file.');
        }
    }

    protected function getSafePath(Request $request, ?string $path): string
    {
        $user = $request->user();
        $defaultPath = "/home/{$user->name}/web";

        if (! $path) {
            return $defaultPath;
        }

        // Ensure path starts with /home/{user->name}/web for security if not admin
        if (! $user->is_admin && ! str_starts_with($path, "/home/{$user->name}/web")) {
            return $defaultPath;
        }

        return $path;
    }
}
