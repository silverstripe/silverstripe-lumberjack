# SilverStripe Lumberjack

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-lumberjack.svg?branch=1.1)](https://travis-ci.org/silverstripe/silverstripe-lumberjack) [![Latest Stable Version](https://poser.pugx.org/silverstripe/lumberjack/v/stable)](https://packagist.org/packages/silverstripe/lumberjack) [![Latest Unstable Version](https://poser.pugx.org/silverstripe/lumberjack/v/unstable)](https://packagist.org/packages/silverstripe/lumberjack) [![License](https://poser.pugx.org/silverstripe/lumberjack/license)](https://packagist.org/packages/silverstripe/lumberjack)

A module to make managing pages in a GridField easy without losing any of the functionality that you're used to in the CMS.

This is intended to be used in cases where the SiteTree grows beyond a manageable level. eg. blogs, news sections, shops, etc.

This module was born out of and decoupled from [micmania1/silverstripe-blog](https://github.com/micmania1/silverstripe-blogger).

## Requirements

	silverstripe/cms: 3.1+


## Installation

	composer require silverstripe/lumberjack

## Features

* Easily define which page types to show in the SiteTree and which to manage in a GridField.
* Keep all functionality that comes with the CMS, including versioning and preview.

## Usage

In this example we have a `NewsHolder` page which is the root of our news section, containing `NewsArticle`s and 
`NewsPage`s. We want to display `NewsPage` in the site tree but we want to display `NewsArticle`s in a `GridField`.

	<?php
	
	class NewsHolder extends Page {
		private static $extensions = array(
			'Lumberjack',
		);
		
		private static $allowed_children = array(
			'NewsArticle',
			'NewsPage',
		);
	}
	
	class NewsArticle extends Page {
		
		private static $show_in_sitetree = false;
		
		private static $allowed_children = array();
		
	}
	
	class NewsPage extends Page {
		
		private static $show_in_sitetree = true;
	
	}
	
If `show_in_sitetree` is not explicitly defined on a class, then it will default to true. You can add this setting to
core classes and modules using the YAML config system. It is **not** recommended to add the LumberJack extension to 
the `SiteTree` or `Page` class.


	:::yaml
	
	BlogHolder:
	  extensions:
	    - 'Lumberjack'
	
	BlogEntry:
	  show_in_sitetree: false
	
### Sorting and Filtering

By default all children in Lumberjack GridField are sorted by SiteTree default `Sort` and can be filtered by their `Title`. This is restrictive but neccessary because there can be multiple different child classes and these are the values they all have in common.

If you are only using one single child class in a Lumberjack Gridfield you can overcome this restriction by setting the `$child classname` to the class extending Lumberjack via config.
This will enable the following functionality:
* `$default_sort` of child class is picked up
* `GridFieldSortableHeader` will work with all variables of the child class
* `GridFieldFilterHeader`will work with all variables of the child class

Add the `$child_classname` value to the class extending Lumberjack via `config.yml`:

```yml
NewsHolder:
  extensions:
    - Lumberjack
  child_classname: 'NewsPage'
```
or via static variable inside the extended Class:

```php
class NewsHolder extends Page {
  private static $extensions = array('Lumberjack');
  private static $child_classname = 'NewsPage'
}
```


