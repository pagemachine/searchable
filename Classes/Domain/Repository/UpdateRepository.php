<?php
namespace PAGEmachine\Searchable\Domain\Repository;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;

class UpdateRepository extends Repository
{
    private const TABLE_NAME = 'tx_searchable_domain_model_update';

    private ConnectionPool $connectionPool;

    public function injectConnectionPool(ConnectionPool $connectionPool): void
    {
        $this->connectionPool = $connectionPool;
    }

    public function initializeObject(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function insertUpdate(
        string $type,
        string $property,
        int $propertyUid,
    ): void {
        try {
            $this->connectionPool
                ->getConnectionForTable(self::TABLE_NAME)
                ->insert(
                    self::TABLE_NAME,
                    [
                        'type' => $type,
                        'property' => $property,
                        'property_uid' => $propertyUid,
                    ],
                );
        } catch (UniqueConstraintViolationException) {
            // Ignore duplicate entry error
        }
    }

    public function deleteAll()
    {
        foreach ($this->findAll() as $object) {
            $this->connectionPool->getConnectionForTable(self::TABLE_NAME)
                ->delete(
                    self::TABLE_NAME,
                    ['uid' => $object->getUid()],
                );
        }
    }
}
