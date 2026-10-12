<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\FieldTypeRichText\Mcp\Tool;

use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\Contracts\Mcp\Test\CapturingTransport;
use Ibexa\Contracts\Mcp\Test\McpToolCallTrait;
use Ibexa\Mcp\Server\McpServerRegistryInterface;

/**
 * Drives the RichText tools through the `richtext` test MCP server (see tests/integration/Resources/ibexa_mcp.yaml).
 */
abstract class AbstractRichTextToolsTestCase extends IbexaKernelTestCase
{
    use McpToolCallTrait;

    private const string SERVER_IDENTIFIER = 'richtext';

    protected function setUp(): void
    {
        parent::setUp();

        self::setAdministratorUser();
    }

    protected function runMcpServer(CapturingTransport $transport): void
    {
        self::getServiceByClassName(McpServerRegistryInterface::class)
            ->getServer(self::SERVER_IDENTIFIER)
            ->run($transport);
    }
}
