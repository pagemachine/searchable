<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector\Utility;

use PHPUnit\Framework\Attributes\Test;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Searchable\DataCollector\Utility\FieldListUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for FieldListUtility
 */
class FieldListUtilityTest extends UnitTestCase
{
    /**
     * @var FieldListUtility
     */
    protected $fieldListUtility;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        $this->fieldListUtility = new FieldListUtility();
    }

    /**
     * @dataProvider whitelistCombinations
     */
    #[Test]
    public function evaluatesWhitelistItem($item, $list, $allowed)
    {
        $this->assertEquals($allowed, $this->fieldListUtility->shouldInclude($item, $list, FieldListUtility::MODE_WHITELIST));
    }

    /**
     *
     * @return array
     */
    public function whitelistCombinations()
    {
        return [
            'item included' => ['allowed', ['allowed', 'foo', 'bar'], true],
            'item not included' => ['notallowed', ['allowed', 'foo', 'bar'], false],
            'empty whitelist' => ['notallowed', [], false],
        ];
    }


    /**
     * @dataProvider blacklistCombinations
     */
    #[Test]
    public function evaluatesBlacklistItem($item, $list, $allowed)
    {
        $this->assertEquals($allowed, $this->fieldListUtility->shouldInclude($item, $list, FieldListUtility::MODE_BLACKLIST));
    }

    /**
     *
     * @return array
     */
    public function blacklistCombinations()
    {
        return [
            'item included' => ['notallowed', ['notallowed', 'foo', 'bar'], false],
            'item not included' => ['allowed', ['notallowed', 'foo', 'bar'], true],
            'empty blacklist' => ['allowed', [], true],
        ];
    }
}
