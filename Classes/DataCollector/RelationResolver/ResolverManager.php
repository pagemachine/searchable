<?php
namespace PAGEmachine\Searchable\DataCollector\RelationResolver;

use PAGEmachine\Searchable\DataCollector\DataCollectorInterface;
use TYPO3\CMS\Core\SingletonInterface;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * 
 */
class ResolverManager implements SingletonInterface {

    /**
     * @var array
     */
	protected $relationResolvers = [
        'FormEngine' => [
            'select' => \PAGEmachine\Searchable\DataCollector\RelationResolver\FormEngine\SelectRelationResolver::class,
            'inline' => \PAGEmachine\Searchable\DataCollector\RelationResolver\FormEngine\InlineRelationResolver::class
        ]
		
	];

    /**
     * Looks up a relation resolver, passes on the arguments and returns the result
     *
     * @param  string $fieldname The name of the field. Either represents the database/TCA fieldname or - in other cases - just the array key
     * @param  array $record The record containing the field to resolve
     * @param  DataCollectorInterface $childCollector
     * @param  DataCollectorInterface $parentCollector
     * @return array $processedField
     */
    public function resolveRelation($fieldname, $record, DataCollectorInterface $childCollector, DataCollectorInterface $parentCollector) {

        $parentConfiguration = $parentCollector->getConfiguration();
        $subConfiguration = $childCollector->getConfiguration();
        $tca = $GLOBALS['TCA'][$parentConfiguration['table']];

        $classname = null;

        // Check for a custom resolver first
        if (isset($subConfiguration['resolver'])) {

            $classname = $subConfiguration['resolver'];
        }
        //Next try TCA/FormEngine related stuff
        else if ($tca['columns'][$fieldname] && $tca['columns'][$fieldname]['config']['type']) {

            if (!empty($this->relationResolvers['FormEngine'][$tca['columns'][$fieldname]['config']['type']])) {

                 $classname = $this->relationResolvers['FormEngine'][$tca['columns'][$fieldname]['config']['type']];
            } else {

                throw new \Exception('No TCA relation resolver for type "' . $tca['columns'][$fieldname]['config']['type'] . '" found.', 1488368425);
            }
        }

        if ($classname != null) {

            $resolver = $classname::getInstance();
            return $resolver->resolveRelation($fieldname, $record, $childCollector, $parentCollector);
        }
        else {

            throw new \Exception('No relation resolver for field "' . $fieldname . '" found.', 1488369044);            
        }
    }




}
