<?php

use App\Mcp\Servers\SuperCP;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', SuperCP::class);
