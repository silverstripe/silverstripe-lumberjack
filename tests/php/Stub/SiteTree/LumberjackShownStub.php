<?php

namespace SilverStripe\Lumberjack\Tests\Stub\SiteTree;

use SilverStripe\Dev\TestOnly;

class LumberjackShownStub extends LumberjackHiddenStub implements TestOnly
{
    private static $show_in_sitetree = true;
}
