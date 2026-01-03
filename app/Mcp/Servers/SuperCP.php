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
        This server allows you to manage web domains in SuperCP. You can list existing domains and create new ones.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        \App\Mcp\Tools\ListDomains::class,
        \App\Mcp\Tools\CreateDomain::class,
        \App\Mcp\Tools\DeleteDomain::class,
        \App\Mcp\Tools\UpdateDomain::class,
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
