<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Database;

use Doctrine\DBAL\Connection;

class TransactionService implements TransactionServiceInterface
{
    /**
     * @var ConnectionProviderInterface
     */
    private $connectionProvider;

    /**
     * @param ConnectionProviderInterface $connectionProvider
     */
    public function __construct(ConnectionProviderInterface $connectionProvider)
    {
        $this->connectionProvider = $connectionProvider;
    }

    /**
     * Initiates a transaction.
     */
    public function begin()
    {
        $this->connectionProvider->get()->beginTransaction();
    }

    /**
     * Commits a transaction.
     */
    public function commit()
    {
        $this->connectionProvider->get()->commit();
    }

    /**
     * Rolls back the current transaction.
     */
    public function rollback()
    {
        $this->connectionProvider->get()->rollBack();
    }
}
