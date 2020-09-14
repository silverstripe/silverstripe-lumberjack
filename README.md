# SilverStripe Lumberjack

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-lumberjack.svg?branch=master)](https://travis-ci.org/silverstripe/silverstripe-lumberjack)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/silverstripe/silverstripe-lumberjack/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/silverstripe/silverstripe-lumberjack/?branch=master)
[![SilverStripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)
[![Code coverage](https://codecov.io/gh/silverstripe/silverstripe-lumberjack/branch/master/graph/badge.svg)](https://codecov.io/gh/silverstripe/silverstripe-lumberjack)

A module to make managing pages in a GridField easy without losing any of the functionality that you're used to in the CMS.

This is intended to be used in cases where the SiteTree grows beyond a manageable level. eg. blogs, news sections, shops, etc.

This module was born out of and decoupled from [micmania1/silverstripe-blog](https://github.com/micmania1/silverstripe-blogger).

## Requirements

* silverstripe/cms: 4.0+

## Installation

```bash
composer require silverstripe/lumberjack
```

## Features

* Easily define which page types to show in the SiteTree and which to manage in a GridField.
* Keep all functionality that comes with the CMS, including versioning and preview.

## Usage

In this example we have a `NewsHolder` page which is the root of our news section, containing `NewsArticle`s and
`NewsPage`s. We want to display `NewsPage` in the site tree but we want to display `NewsArticle`s in a `GridField`.

```php
<?php

namespace MyModule\PageTypes;

use Page;
use SilverStripe\Lumberjack\Model\Lumberjack;

class NewsHolder extends Page
{
    private static $extensions = [
        Lumberjack::class,
    ];

    private static $allowed_children = [
        NewsArticle::class,
        NewsPage::class,
    ];
}
```

```php
<?php

namespace MyModule\PageTypes;

use Page;

class NewsArticle extends Page
{
    private static $show_in_sitetree = false;
    private static $allowed_children = [];
}
```

```php
<?php

namespace MyModule\PageTypes;

use Page;

class NewsPage extends Page
{
    private static $show_in_sitetree = true;
}
```

If `show_in_sitetree` is not explicitly defined on a class, then it will default to true. You can add this setting to
core classes and modules using the YAML config system. It is **not** recommended to add the LumberJack extension to
the `SiteTree` or `Page` class.


```yaml
SilverStripe\Blog\Model\Blog:
  extensions:
    - SilverStripe\Lumberjack\Model\Lumberjack

SilverStripe\Blog\Model\BlogPost:
  show_in_sitetree: false
```

## Use with SortableGridField

Install [Sortable Grid Field](https://github.com/UndefinedOffset/SortableGridField) if you don't have it already.

```bash
composer require undefinedoffset/sortablegridfield
```

This example uses the same `NewsHolder` and `NewsArticle` pages from the usage example above.

```php
<?php

namespace MyModule\PageTypes;

use Page;
use SilverStripe\Lumberjack\Model\Lumberjack;
use SilverStripe\Lumberjack\Forms\GridFieldConfig_Lumberjack;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;

class NewsHolder extends Page
{
    private static $extensions = [
        Lumberjack::class,
    ];

    private static $allowed_children = [
        NewsArticle::class
    ];

    private static $owns = ['Children'];

    public function getLumberjackGridFieldConfig()
    {
        $gridfield = new GridFieldConfig_Lumberjack(10); // 10 per page
        $gridfield->addComponent(new GridFieldSortableRows('SortOrder'));
        return $gridfield;
    }

    public function getLumberjackPagesForGridfield($excluded = array())
    {
        return NewsArticle::get()->filter([
            'ParentID' => $this->owner->ID,
            'ClassName' => $excluded,
        ]);
    }
}
```

```php
<?php

namespace MyModule\PageTypes;

use Page;

class NewsArticle extends Page
{
    private static $db = [
        'SortOrder' => 'Int'
    ];

    private static $indexes = [
        'SortOrder' => true
    ];

    private static $show_in_sitetree = false;
    private static $allowed_children = [];
}
```
