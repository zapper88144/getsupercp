<?php

namespace App\Services;

use App\Models\MonitoringAlert;
use App\Notifications\SystemAlertNotification;
use App\Traits\HandlesDaemonErrors;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MonitoringService
{
    use HandlesDaemonErrors;

    public function __construct(private RustDaemonClient $daemon) {}

    public function getSystemStats(): array
    {
        return $this->handleDaemonCall(function () {
            $result = $this->daemon->call('get_system_stats');

            return $result['result'] ?? $result;
        }, 'Failed to get system stats');
    }

    public function getMetrics(): array
    {
        try {
            return $this->handleDaemonCall(function () {
                $result = $this->daemon->call('get_system_stats');

                $cpu = $result['cpu_usage'] ?? 0;

                $memTotal = $result['memory']['total'] ?? 1;
                $memUsed = $result['memory']['used'] ?? 0;
                $memPercentage = ($memUsed / $memTotal) * 100;

                $diskPercentage = 0;
                if (! empty($result['disks'])) {
                    $disk = $result['disks'][0];
                    $diskTotal = $disk['total'] ?? 1;
                    $diskAvailable = $disk['available'] ?? 0;
                    $diskUsed = $diskTotal - $diskAvailable;
                    $diskPercentage = ($diskUsed / $diskTotal) * 100;
                }

                return [
                    'cpu_percentage' => $cpu,
                    'memory_percentage' => $memPercentage,
                    'disk_percentage' => $diskPercentage,
                    'bandwidth_usage' => 0, // Not directly available in simple format
                    'load_average' => $result['load_average'][0] ?? 0,
                    'timestamp' => now(),
                ];
            }, 'Failed to get system metrics');
        } catch (Exception $e) {
            Log::error('Failed to get system metrics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'cpu_percentage' => 0,
                'memory_percentage' => 0,
                'disk_percentage' => 0,
                'bandwidth_usage' => 0,
                'load_average' => 0,
                'timestamp' => now(),
            ];
        }
    }

    public function checkThresholds(MonitoringAlert $alert): bool
    {
        try {
            $metrics = $this->getMetrics();
            $metricValue = $metrics[$alert->metric.'_percentage'] ?? 0;

            return match ($alert->comparison) {
                '>' => $metricValue > $alert->threshold_percentage,
                '>=' => $metricValue >= $alert->threshold_percentage,
                '<' => $metricValue < $alert->threshold_percentage,
                '<=' => $metricValue <= $alert->threshold_percentage,
                '==' => $metricValue == $alert->threshold_percentage,
                '!=' => $metricValue != $alert->threshold_percentage,
                default => false,
            };
        } catch (Exception $e) {
            Log::error('Error checking alert thresholds', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function processAlert(MonitoringAlert $alert): void
    {
        if (! $alert->is_enabled) {
            return;
        }

        $isTriggered = $this->checkThresholds($alert);

        if ($isTriggered) {
            $this->triggerAlert($alert);
        } else {
            $this->resetAlert($alert);
        }
    }

    public function triggerAlert(MonitoringAlert $alert): void
    {
        try {
            $consecutiveCount = ($alert->consecutive_triggers ?? 0) + 1;

            $alert->update([
                'triggered_at' => now(),
                'consecutive_triggers' => $consecutiveCount,
            ]);

            if ($this->shouldNotify($alert, $consecutiveCount)) {
                $this->sendNotifications($alert);
                $alert->update(['last_notification_at' => now()]);
            }
        } catch (Exception $e) {
            Log::error('Failed to trigger alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function resetAlert(MonitoringAlert $alert): void
    {
        if ($alert->triggered_at !== null) {
            $alert->update([
                'triggered_at' => null,
                'consecutive_triggers' => 0,
            ]);
        }
    }

    private function shouldNotify(MonitoringAlert $alert, int $consecutiveCount): bool
    {
        return match ($alert->frequency) {
            'immediate' => true,
            '5min' => $alert->last_notification_at === null || $alert->last_notification_at->diffInMinutes(now()) >= 5,
            '15min' => $alert->last_notification_at === null || $alert->last_notification_at->diffInMinutes(now()) >= 15,
            '30min' => $alert->last_notification_at === null || $alert->last_notification_at->diffInMinutes(now()) >= 30,
            '1hour' => $alert->last_notification_at === null || $alert->last_notification_at->diffInHours(now()) >= 1,
            default => false,
        };
    }

    private function sendNotifications(MonitoringAlert $alert): void
    {
        try {
            if ($alert->notify_email && $alert->user->email) {
                $this->sendEmailNotification($alert);
            }

            if ($alert->notify_webhook && $alert->webhook_url) {
                $this->sendWebhookNotification($alert);
            }
        } catch (Exception $e) {
            Log::error('Failed to send alert notifications', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendEmailNotification(MonitoringAlert $alert): void
    {
        try {
            $metrics = $this->getMetrics();
            $metricValue = $metrics[$alert->metric.'_percentage'] ?? 0;

            if (! isset($alert->user) || ! $alert->user->email) {
                return;
            }

            $subject = "Monitoring Alert: {$alert->name}";
            $message = "The metric '{$alert->metric}' has reached {$metricValue}% (Threshold: {$alert->threshold_percentage}%).";

            $alertData = [
                'Metric' => $alert->metric,
                'Current Value' => $metricValue.'%',
                'Threshold' => $alert->threshold_percentage.'%',
                'Comparison' => $alert->comparison,
            ];

            Notification::send($alert->user, new SystemAlertNotification($subject, $message, $alertData));
        } catch (Exception $e) {
            Log::error('Failed to send email notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendWebhookNotification(MonitoringAlert $alert): void
    {
        try {
            $metrics = $this->getMetrics();
            $metricValue = $metrics[$alert->metric.'_percentage'] ?? 0;

            Http::post($alert->webhook_url, [
                'alert_name' => $alert->name,
                'metric' => $alert->metric,
                'current_value' => $metricValue,
                'threshold' => $alert->threshold_percentage,
                'comparison' => $alert->comparison,
                'consecutive_triggers' => $alert->consecutive_triggers,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send webhook notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function isDaemonRunning(): bool
    {
        try {
            $result = $this->daemon->call('ping');

            return $result === 'pong';
        } catch (Exception $e) {
            return false;
        }
    }
}
