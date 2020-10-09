<?php
declare(strict_types = 1);

namespace Pagemachine\SearchableExtbaseL10nTest\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

final class Content extends AbstractEntity
{
    /**
     * @var string
     */
    protected $header;

    public function getHeader(): string
    {
        return $this->header;
    }

    public function setHeader(string $header): void
    {
        $this->header = $header;
    }
}
