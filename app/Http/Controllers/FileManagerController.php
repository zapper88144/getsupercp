<?php

namespace App\Http\Controllers;

use App\Services\RustDaemonClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FileManagerController extends Controller
{
    public function __construct(protected RustDaemonClient $daemon) {}

    public function index(): Response
    {
        return Inertia::render('FileManager/Index');
    }

    public function list(Request $request)
    {
        $path = $request->get('path', '/');
        $response = $this->daemon->call('list_files', ['path' => $path]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 400);
        }

        return response()->json($response['result']);
    }

    public function read(Request $request)
    {
        $path = $request->get('path');
        $response = $this->daemon->call('read_file', ['path' => $path]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 400);
        }

        return response()->json(['content' => $response['result']]);
    }

    public function write(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'content' => 'required|string',
        ]);

        $response = $this->daemon->call('write_file', [
            'path' => $request->path,
            'content' => $request->content,
        ]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 400);
        }

        return response()->json(['message' => $response['result']]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $response = $this->daemon->call('delete_file', ['path' => $request->path]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 400);
        }

        return response()->json(['message' => $response['result']]);
    }

    public function createDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $response = $this->daemon->call('create_directory', ['path' => $request->path]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 400);
        }

        return response()->json(['message' => $response['result']]);
    }

    public function rename(Request $request)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
        ]);

        $response = $this->daemon->call('rename_file', [
            'from' => $request->from,
            'to' => $request->to,
        ]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 400);
        }

        return response()->json(['message' => $response['result']]);
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

        $response = $this->daemon->call('write_file', [
            'path' => $targetPath,
            'content' => $content,
        ]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 400);
        }

        return response()->json(['message' => 'File uploaded successfully']);
    }

    public function download(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $response = $this->daemon->call('read_file', ['path' => $request->path]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 400);
        }

        $filename = basename($request->path);
        
        return response($response['result'])
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
