<?php

namespace SilverStripe\Lumberjack\Tests\Stub\SiteTree;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Lumberjack\Model\Lumberjack;

class LumberjackStub extends SiteTree implements TestOnly
{
    private static $extensions = [Lumberjack::class];
}
