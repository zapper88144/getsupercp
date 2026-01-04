<?php

namespace App\Services;

use App\Traits\HandlesDaemonErrors;

class FileService
{
    use HandlesDaemonErrors;

    private RustDaemonClient $daemon;

    public function __construct(?RustDaemonClient $daemon = null)
    {
        $this->daemon = $daemon ?? new RustDaemonClient;
    }

    /**
     * List files in a directory
     */
    public function listFiles(string $path): array
    {
        return $this->handleDaemonCall(function () use ($path) {
            return $this->daemon->listFiles($path);
        }, "Failed to list files in {$path}");
    }

    /**
     * Read file content
     */
    public function readFile(string $path): string
    {
        return $this->handleDaemonCall(function () use ($path) {
            return $this->daemon->readFile($path);
        }, "Failed to read file {$path}");
    }

    /**
     * Write file content
     */
    public function writeFile(string $path, string $content): string
    {
        return $this->handleDaemonCall(function () use ($path, $content) {
            return $this->daemon->writeFile($path, $content);
        }, "Failed to write file {$path}");
    }

    /**
     * Delete file or directory
     */
    public function deleteFile(string $path): string
    {
        return $this->handleDaemonCall(function () use ($path) {
            return $this->daemon->deleteFile($path);
        }, "Failed to delete file {$path}");
    }

    /**
     * Create directory
     */
    public function createDirectory(string $path): string
    {
        return $this->handleDaemonCall(function () use ($path) {
            return $this->daemon->createDirectory($path);
        }, "Failed to create directory {$path}");
    }

    /**
     * Rename file or directory
     */
    public function renameFile(string $from, string $to): string
    {
        return $this->handleDaemonCall(function () use ($from, $to) {
            return $this->daemon->renameFile($from, $to);
        }, "Failed to rename file from {$from} to {$to}");
    }
}
