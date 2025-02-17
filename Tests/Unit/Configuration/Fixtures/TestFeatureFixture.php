<?php
namespace PAGEmachine\Searchable\Tests\Unit\Configuration\Fixtures;

use PAGEmachine\Searchable\Feature\AbstractFeature;
use PAGEmachine\Searchable\Feature\FeatureInterface;

/*
 * This file is part of the Pagemachine Searchable project.
 */


class TestFeatureFixture extends AbstractFeature implements FeatureInterface
{
    /**
     * Entry point to modify mapping
     *
     * @param  array  $mapping
     * @param  array  $configuration
     * @return array  $mapping
     */
    public static function modifyMapping($mapping, $configuration)
    {
        $mapping['featureproperty'] = 'featurevalue';

        return $mapping;
    }
}
