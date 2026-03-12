<?php
namespace PAGEmachine\Searchable\Queue;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;

final readonly class UpdateQueue
{
    private const TABLE_NAME = 'tx_searchable_update';

    public function __construct(private ConnectionPool $connectionPool)
    {
    }

    public function enqueue(
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

    public function pendingUpdates(string $type = null): array
    {
        $queryBuilder = $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME);

        if ($type) {
            $queryBuilder
                ->where(
                    $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter($type, ParameterType::STRING)),
                );
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    public function getMaxUid(): ?int
    {
        $result = $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->createQueryBuilder()
            ->select('uid')
            ->from(self::TABLE_NAME)
            ->orderBy('uid', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return $result === false ? null : (int)$result;
    }

    public function clear(string $type, int $maxUid): void
    {
        $queryBuilder = $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->createQueryBuilder();

        $queryBuilder
            ->delete(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter($type, ParameterType::STRING)),
                $queryBuilder->expr()->lte('uid', $queryBuilder->createNamedParameter($maxUid, ParameterType::INTEGER)),
            )
            ->executeStatement();
    }

    public function clearAll(): void
    {
        $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->createQueryBuilder()
            ->delete(self::TABLE_NAME)
            ->executeStatement();
    }
}
