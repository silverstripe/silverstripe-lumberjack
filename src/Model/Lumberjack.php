<?php

namespace SilverStripe\Lumberjack\Model;

use Exception;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\Tab;
use SilverStripe\Lumberjack\Forms\GridFieldConfig_Lumberjack;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Class Lumberjack
 *
 * Add this classes to SiteTree classes which children should be hidden.
 *
 * @package silverstripe
 * @subpackage lumberjack
 *
 * @author Michael Strong <mstrong@silverstripe.org>
 */
class Lumberjack extends SiteTreeExtension
{
    /**
     * Loops through subclasses of the owner (intended to be SiteTree) and checks if they've been hidden.
     *
     * @return array
     **/
    public function getExcludedSiteTreeClassNames()
    {
        $classes = array();
        $siteTreeClasses = $this->owner->allowedChildren();

        foreach ($siteTreeClasses as $class) {
            if (Config::inst()->get($class, 'show_in_sitetree') === false) {
                $classes[$class] = $class;
            }
        }

        return $classes;
    }

    /**
     * This is responsible for adding the child pages tab and gridfield.
     *
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $excluded = $this->owner->getExcludedSiteTreeClassNames();
        if (!empty($excluded)) {
            $pages = $this->getLumberjackPagesForGridfield($excluded);
            $gridField = GridField::create(
                'ChildPages',
                $this->getLumberjackTitle(),
                $pages,
                $this->getLumberjackGridFieldConfig()
            );

            $tab = Tab::create('ChildPages', $this->getLumberjackTitle(), $gridField);
            $fields->insertAfter($tab, 'Main');
        }
    }

    /**
     * Return children in the stage site.
     *
     * @param bool $showAll Include all of the elements, even those not shown in the menus. Only applicable when
     *                      extension is applied to {@link SiteTree}.
     * @return DataList
     */
    public function stageChildren($showAll = false)
    {
        $config = $this->owner->config();
        $hideFromHierarchy = $config->get('hide_from_hierarchy');
        $hideFromCMSTree = $config->get('hide_from_cms_tree');
        $baseClass = $this->owner->baseClass();

        $staged = $baseClass::get()
            ->filter('ParentID', (int)$this->owner->ID)
            ->exclude('ID', (int)$this->owner->ID);

        if ($hideFromHierarchy) {
            $staged = $staged->exclude('ClassName', $hideFromHierarchy);
        }

        if ($hideFromCMSTree && $this->showingCMSTree()) {
            $staged = $staged->exclude('ClassName', $hideFromCMSTree);
        }

        if (!$showAll && DataObject::getSchema()->fieldSpec($this->owner, 'ShowInMenus')) {
            $staged = $staged->filter('ShowInMenus', 1);
        }

        $this->owner->extend("augmentStageChildren", $staged, $showAll);
        $staged = $this->excludeSiteTreeClassNames($staged);
        return $staged;
    }

    /**
     * Excludes any hidden owner subclasses. Note that the returned DataList will be a different
     * instance from the original.
     *
     * @param DataList $list
     * @return DataList
     */
    protected function excludeSiteTreeClassNames($list)
    {
        $classNames = $this->owner->getExcludedSiteTreeClassNames();
        if ($this->shouldFilter() && count($classNames)) {
            // Filter the SiteTree
            $list = $list->exclude('ClassName', $classNames);
        }
        return $list;
    }

    /**
     * Return children in the live site, if it exists.
     *
     * @param bool $showAll              Include all of the elements, even those not shown in the menus. Only
     *                                   applicable when extension is applied to {@link SiteTree}.
     * @param bool $onlyDeletedFromStage Only return items that have been deleted from stage
     * @return DataList
     * @throws Exception
     */
    public function liveChildren($showAll = false, $onlyDeletedFromStage = false)
    {
        if (!$this->owner->hasExtension(Versioned::class)) {
            throw new Exception('Hierarchy->liveChildren() only works with Versioned extension applied');
        }

        $config = $this->owner->config();
        $hideFromHierarchy = $config->get('hide_from_hierarchy');
        $hideFromCMSTree = $config->get('hide_from_cms_tree');
        $baseClass = $this->owner->baseClass();

        $children = $baseClass::get()
            ->filter('ParentID', (int)$this->owner->ID)
            ->exclude('ID', (int)$this->owner->ID)
            ->setDataQueryParam(array(
                'Versioned.mode' => $onlyDeletedFromStage ? 'stage_unique' : 'stage',
                'Versioned.stage' => 'Live'
            ));

        if ($hideFromHierarchy) {
            $children = $children->exclude('ClassName', $hideFromHierarchy);
        }

        if ($hideFromCMSTree && $this->showingCMSTree()) {
            $children = $children->exclude('ClassName', $hideFromCMSTree);
        }

        if (!$showAll && DataObject::getSchema()->fieldSpec($this->owner, 'ShowInMenus')) {
            $children = $children->filter('ShowInMenus', 1);
        }
        $children = $this->excludeSiteTreeClassNames($children);

        return $children;
    }

    /**
     * This returns the title for the tab and GridField. This can be overwritten
     * in the owner class.
     *
     * @return string
     */
    protected function getLumberjackTitle()
    {
        if (method_exists($this->owner, 'getLumberjackTitle')) {
            return $this->owner->getLumberjackTitle();
        }

        return _t('Lumberjack.TabTitle', 'Child Pages');
    }

    /**
     * This returns the gird field config for the lumberjack gridfield.
     *
     * @return GridFieldConfig_Lumberjack
     */
    protected function getLumberjackGridFieldConfig()
    {
        if (method_exists($this->owner, 'getLumberjackGridFieldConfig')) {
            return $this->owner->getLumberjackGridFieldConfig();
        }

        return GridFieldConfig_Lumberjack::create();
    }

    /**
     * Checks if we're on a controller where we should filter. ie. Are we loading the SiteTree?
     * NB: This only checks the current controller. See https://github.com/silverstripe/silverstripe-lumberjack/pull/60
     * for a discussion around this.
     *
     * @return bool
     */
    protected function shouldFilter()
    {
        $controller = Controller::curr();

        // relevant only for CMS
        if (!($controller instanceof LeftAndMain)) {
            return false;
        }

        return in_array($controller->getAction(), [
            'index', 'show', 'treeview', 'listview', 'getsubtree'
        ]);
    }

    /**
     * Returns list of pages for the CMS gridfield
     *
     * This also allows the owner class to override this method, e.g. to provide custom ordering.
     *
     * @var array $excluded     List of class names excluded from the SiteTree
     * @return DataList
     */
    public function getLumberjackPagesForGridfield($excluded = array())
    {
        if (method_exists($this->owner, 'getLumberjackPagesForGridfield')) {
            return $this->owner->getLumberjackPagesForGridfield($excluded);
        }

        return SiteTree::get()->filter([
            'ParentID' => $this->owner->ID,
            'ClassName' => $excluded,
        ]);
    }
}
