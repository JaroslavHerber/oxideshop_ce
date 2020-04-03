<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Database;

use Doctrine\DBAL\Connection;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionProvider;

class ConnectionProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConnection()
    {
        $connectionProvider = new ConnectionProvider();

        $this->assertInstanceOf(
            Connection::class,
            $connectionProvider->get()
        );
    }
}
