<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;

class SuperCP extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Super C P';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        This server allows you to manage SuperCP (Super Control Panel).
        You can manage web domains, databases, FTP users, firewall rules, cron jobs, DNS zones, email accounts, backups, and files.
        You can also view system statistics, logs, and manage Redis cache/data storage.
        Always start by listing users if you need to find a user ID for resource management.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        \App\Mcp\Tools\ListUsers::class,
        \App\Mcp\Tools\ListAuditLogs::class,
        \App\Mcp\Tools\ListNotifications::class,
        \App\Mcp\Tools\GetSystemStats::class,
        \App\Mcp\Tools\ListDomains::class,
        \App\Mcp\Tools\CreateDomain::class,
        \App\Mcp\Tools\DeleteDomain::class,
        \App\Mcp\Tools\UpdateDomain::class,
        \App\Mcp\Tools\ListDatabases::class,
        \App\Mcp\Tools\CreateDatabase::class,
        \App\Mcp\Tools\DeleteDatabase::class,
        \App\Mcp\Tools\ListFtpUsers::class,
        \App\Mcp\Tools\CreateFtpUser::class,
        \App\Mcp\Tools\DeleteFtpUser::class,
        \App\Mcp\Tools\ListFirewallRules::class,
        \App\Mcp\Tools\CreateFirewallRule::class,
        \App\Mcp\Tools\DeleteFirewallRule::class,
        \App\Mcp\Tools\ListCronJobs::class,
        \App\Mcp\Tools\CreateCronJob::class,
        \App\Mcp\Tools\DeleteCronJob::class,
        \App\Mcp\Tools\ListDnsZones::class,
        \App\Mcp\Tools\CreateDnsZone::class,
        \App\Mcp\Tools\DeleteDnsZone::class,
        \App\Mcp\Tools\ListDnsRecords::class,
        \App\Mcp\Tools\UpdateDnsRecords::class,
        \App\Mcp\Tools\ListEmailAccounts::class,
        \App\Mcp\Tools\CreateEmailAccount::class,
        \App\Mcp\Tools\DeleteEmailAccount::class,
        \App\Mcp\Tools\ListBackups::class,
        \App\Mcp\Tools\CreateBackup::class,
        \App\Mcp\Tools\RestoreBackup::class,
        \App\Mcp\Tools\DeleteBackup::class,
        \App\Mcp\Tools\ListFiles::class,
        \App\Mcp\Tools\ReadFile::class,
        \App\Mcp\Tools\WriteFile::class,
        \App\Mcp\Tools\DeleteFile::class,
        \App\Mcp\Tools\CreateDirectory::class,
        \App\Mcp\Tools\RenameFile::class,
        \App\Mcp\Tools\GetLogs::class,
        \App\Mcp\Tools\GetRedisInfo::class,
        \App\Mcp\Tools\ListRedisKeys::class,
        \App\Mcp\Tools\GetRedisKey::class,
        \App\Mcp\Tools\SetRedisKey::class,
        \App\Mcp\Tools\DeleteRedisKey::class,
        \App\Mcp\Tools\FlushRedisDatabase::class,
        \App\Mcp\Tools\GetRedisStats::class,
        \App\Mcp\Tools\PingRedisServer::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
