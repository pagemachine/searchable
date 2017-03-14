<?php
namespace PAGEmachine\Searchable\Enumeration;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class TcaType extends \TYPO3\CMS\Core\Type\Enumeration {

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
     * @return boolean
     */
    public function isPlainMappingType() {

        return in_array($this->__toString(), [
            self::INPUT,
            self::TEXT,
            self::CHECK,
            self::RADIO
        ]);
    }

    /**
     * Returns the default Elasticsearch mapping for this type
     *
     * @return string
     */
    public function convertToESType() {

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
