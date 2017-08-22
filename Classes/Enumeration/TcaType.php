<?php
namespace PAGEmachine\Searchable\Enumeration;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class TcaType extends \TYPO3\CMS\Core\Type\Enumeration
{
    const INPUT = "input";
    const TEXT = "text";
    const CHECK = "check";
    const RADIO = "radio";
    const SELECT = "select";
    const GROUP = "group";
    const NONE = "none";
    const PASSTHROUGH = "passthrough";
    const USER = "user";
    const FLEX = "flex";
    const INLINE = "inline";

    /**
     * Returns true if the type is of plain mapping type (supported type with no relations)
     *
     * @param $type
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
     * @param $type
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
     * @param $type
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
        switch ($this->__toString()) {
            case self::INPUT:
            case self::TEXT:
                return 'text';

            case self::RADIO:
                return 'keyword';

            default:
                return 'text';
        }
    }
}
