<?php

namespace App\Services;

use App\Traits\HandlesDaemonErrors;

class SystemService
{
    use HandlesDaemonErrors;

    private RustDaemonClient $daemon;

    public function __construct(?RustDaemonClient $daemon = null)
    {
        $this->daemon = $daemon ?? new RustDaemonClient;
    }

    /**
     * Get system status
     */
    public function getStatus(): array
    {
        return $this->handleDaemonCall(function () {
            return $this->daemon->getStatus();
        }, 'Failed to get system status');
    }

    /**
     * Restart a service
     */
    public function restartService(string $service): string
    {
        return $this->handleDaemonCall(function () use ($service) {
            return $this->daemon->restartService($service);
        }, "Failed to restart service {$service}");
    }

    /**
     * Get service logs
     */
    public function getServiceLogs(string $service, int $lines = 50): string
    {
        return $this->handleDaemonCall(function () use ($service, $lines) {
            return $this->daemon->getServiceLogs($service, $lines);
        }, "Failed to get logs for service {$service}");
    }

    /**
     * Get system stats
     */
    public function getSystemStats(): array
    {
        return $this->handleDaemonCall(function () {
            return $this->daemon->getSystemStats();
        }, 'Failed to get system statistics');
    }
}
