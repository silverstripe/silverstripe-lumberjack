<?php

namespace SilverStripe\Lumberjack\Tests\Stub\SiteTree;

use SilverStripe\Dev\TestOnly;

class LumberjackHiddenStub extends LumberjackStub implements TestOnly
{
    private static $show_in_sitetree = false;
}
