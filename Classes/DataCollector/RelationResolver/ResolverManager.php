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
class ResolverManager implements SingletonInterface
{
    /**
     * @var array
     */
    protected $relationResolvers = [
        'FormEngine' => [
            'select' => \PAGEmachine\Searchable\DataCollector\RelationResolver\FormEngine\SelectRelationResolver::class,
            'inline' => \PAGEmachine\Searchable\DataCollector\RelationResolver\FormEngine\InlineRelationResolver::class,
        ],

    ];

    /**
     * Finds a suitable resolver
     *
     * @param  string                 $fieldname
     * @param  DataCollectorInterface $childCollector
     * @param  DataCollectorInterface $parentCollector
     * @return RelationResolverInterface
     */
    public function findResolverForRelation($fieldname, DataCollectorInterface $childCollector, DataCollectorInterface $parentCollector)
    {
        $parentConfiguration = $parentCollector->getConfig();
        $subConfiguration = $childCollector->getConfig();
        $tca = $GLOBALS['TCA'][$parentConfiguration['table']];

        $classname = null;

        // Check for a custom resolver first
        if (is_array($subConfiguration['resolver']) && $subConfiguration['resolver']['className']) {
            $classname = $subConfiguration['resolver']['className'];
        //Next try TCA/FormEngine related stuff
        } elseif ($tca['columns'][$fieldname] && $tca['columns'][$fieldname]['config']['type']) {
            if (!empty($this->relationResolvers['FormEngine'][$tca['columns'][$fieldname]['config']['type']])) {
                 $classname = $this->relationResolvers['FormEngine'][$tca['columns'][$fieldname]['config']['type']];
            } else {
                throw new \Exception('No TCA relation resolver for type "' . $tca['columns'][$fieldname]['config']['type'] . '" found.', 1488368425);
            }
        }

        if ($classname != null) {
            $resolver = $classname::getInstance();
            return $resolver;
        } else {
            throw new \Exception('No relation resolver for field "' . $fieldname . '" found.', 1488369044);
        }
    }
}
