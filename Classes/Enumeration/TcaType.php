<?php
namespace PAGEmachine\Searchable\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;
/*
 * This file is part of the PAGEmachine Searchable project.
 */
class TcaType extends Enumeration
{
    public const INPUT = "input";
    public const TEXT = "text";
    public const CHECK = "check";
    public const RADIO = "radio";
    public const SELECT = "select";
    public const GROUP = "group";
    public const NONE = "none";
    public const PASSTHROUGH = "passthrough";
    public const USER = "user";
    public const FLEX = "flex";
    public const INLINE = "inline";

    /**
     * Returns true if the type is of plain mapping type (supported type with no relations)
     *
     * @param string $type
     * @return bool
     */
    public static function isPlain($type)
    {
        return in_array($type, [
            self::INPUT,
            self::TEXT,
            self::CHECK,
            self::RADIO,
        ]);
    }

    /**
     * Returns true if the type is relation based (and needs subcollector handling)
     *
     * @param string $type
     * @return bool
     */
    public static function isRelation($type)
    {
        return in_array($type, [
            self::SELECT,
            self::INLINE,
        ]);
    }

    /**
     * Returns true if the type is currently unsupported by this extension
     *
     * @param string $type
     * @return bool
     */
    public static function isUnsupported($type)
    {
        return in_array($type, [
            self::NONE,
            self::PASSTHROUGH,
            self::USER,
            self::FLEX,
            self::GROUP, //Group type is not supported yet even if it is a valid relation
        ]);
    }

    /**
     * Returns the default Elasticsearch mapping for this type
     *
     * @return string
     */
    public function convertToESType()
    {
        return match ($this->__toString()) {
            self::INPUT, self::TEXT => 'text',
            self::RADIO => 'keyword',
            default => 'text',
        };
    }
}
