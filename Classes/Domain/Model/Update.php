<?php
namespace PAGEmachine\Searchable\Domain\Model;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Update extends AbstractEntity
{
    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var string
     */
    protected $property = '';

    /**
     * @var int
     */
    protected $propertyUid = 0;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return $this->property;
    }

    /**
     * @param string $property
     */
    public function setProperty(string $property): void
    {
        $this->property = $property;
    }

    /**
     * @return int
     */
    public function getPropertyUid(): int
    {
        return $this->propertyUid;
    }

    /**
     * @param int $propertyUid
     */
    public function setPropertyUid(int $propertyUid): void
    {
        $this->propertyUid = $propertyUid;
    }
}
