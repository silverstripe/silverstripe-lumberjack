<?php

class LumberjackTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    protected $extraDataObjects = array(
        'SiteTree_Lumberjack',
        'SiteTree_LumberjackHidden',
        'SiteTree_LumberjackShown',
    );

    public function testGetExcludedSiteTreeClassNames()
    {
        $standard = $this->objFromFixture('SiteTree_Lumberjack', 'standard');

        $excluded = $standard->getExcludedSiteTreeClassNames();
        $excluded = $this->filteredClassNames($excluded, $this->extraDataObjects);
        $this->assertEquals($excluded, array('SiteTree_LumberjackHidden' => 'SiteTree_LumberjackHidden'));

        Config::inst()->update('SiteTree', 'show_in_sitetree', false);
        $excluded = $standard->getExcludedSiteTreeClassNames();
        $excluded = $this->filteredClassNames($excluded, $this->extraDataObjects);
        $this->assertEquals($excluded, array(
            'SiteTree_Lumberjack' => 'SiteTree_Lumberjack',
            'SiteTree_LumberjackHidden' => 'SiteTree_LumberjackHidden'
        ));
    }


    /**
     * Because we don't know what other test classes are included, we filter to the ones we know
     * and want to test.
     *
     * @param array $classNames
     *
     * @return array
     */
    protected function filteredClassNames($classNames, $explicitClassNames)
    {
        $classNames = array_filter($classNames, function ($value) use ($explicitClassNames) {
            return in_array($value, $explicitClassNames);
        });
        return $classNames;
    }
}

class SiteTree_Lumberjack extends SiteTree implements TestOnly
{
    private static $extensions = array(
        'Lumberjack',
    );
}

class SiteTree_LumberjackHidden extends SiteTree_Lumberjack implements TestOnly
{
    private static $show_in_sitetree = false;
}

class SiteTree_LumberjackShown extends SiteTree_LumberjackHidden implements TestOnly
{
    private static $show_in_sitetree = true;
}
