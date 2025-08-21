<?php

namespace SilverStripe\Lumberjack\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Lumberjack\Tests\Stub\SiteTree\LumberjackStub;
use SilverStripe\Lumberjack\Tests\Stub\SiteTree\LumberjackHiddenStub;
use SilverStripe\Lumberjack\Tests\Stub\SiteTree\LumberjackShownStub;

class LumberjackTest extends SapphireTest
{
    /**
     * {@inheritDoc}
     * @var string
     */
    protected static $fixture_file = 'fixtures.yml';

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        LumberjackStub::class,
        LumberjackHiddenStub::class,
        LumberjackShownStub::class,
    ];

    public function testGetExcludedSiteTreeClassNames()
    {
        $standard = $this->objFromFixture(LumberjackStub::class, 'standard');

        $excluded = $standard->getExcludedSiteTreeClassNames();
        $excluded = $this->filteredClassNames($excluded, LumberjackTest::$extra_dataobjects);
        $this->assertEquals($excluded, array(LumberjackHiddenStub::class => LumberjackHiddenStub::class));

        Config::modify()->set(SiteTree::class, 'show_in_sitetree', false);
        $excluded = $standard->getExcludedSiteTreeClassNames();
        $excluded = $this->filteredClassNames($excluded, LumberjackTest::$extra_dataobjects);
        $this->assertEquals(
            $excluded,
            array(
                LumberjackStub::class       => LumberjackStub::class,
                LumberjackHiddenStub::class => LumberjackHiddenStub::class
            )
        );
    }

    /**
     * Because we don't know what other test classes are included, we filter to the ones we know
     * and want to test.
     *
     * @param  array $classNames
     * @param  array $explicitClassNames
     * @return array
     */
    protected function filteredClassNames($classNames, $explicitClassNames)
    {
        $classNames = array_filter($classNames ?? [], function ($value) use ($explicitClassNames) {
            return in_array($value, $explicitClassNames ?? []);
        });
        return $classNames;
    }
}
