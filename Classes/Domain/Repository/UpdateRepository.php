<?php
namespace PAGEmachine\Searchable\Domain\Repository;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class UpdateRepository
{
    private const TABLE_NAME = 'tx_searchable_domain_model_update';

    public function __construct(private readonly ConnectionPool $connectionPool)
    {
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

    public function findAll(): array
    {
        $queryBuilder = $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME);

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    public function findByType(string $type): array
    {
        $queryBuilder = $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter($type, \PDO::PARAM_STR)),
            );

        return $queryBuilder->executeQuery()->fetchAllAssociative();
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
