<?php

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
            $gridField = new GridField(
                "ChildPages",
                $this->getLumberjackTitle(),
                $pages,
                $this->getLumberjackGridFieldConfig()
            );
            $pagesLive = $pages->setDataQueryParam(array(
                'Versioned.mode' => 'stage',
                'Versioned.stage' => 'Live'
            ))->exclude('ID', $pages->column('ID'));

            $tab = new Tab('ChildPages', $this->getLumberjackTitle(), $gridField);
            if ($pagesLive->count() > 0)
            {
                // Allow user to items that have had their draft deleted but were not unpublished
                // This issue should not occur in 3.2+ sites but occurs in 3.1 as deleting draft/unpublishing
                // are seperate actions.
                $gridFieldLive = new GridField(
                    "ChildPagesLive",
                    $this->getLumberjackTitle() . ' (Items to be deleted)',
                    $pagesLive,
                    $config = $this->getLumberjackGridFieldConfig()
                );
                $config->removeComponentsByType('GridFieldSiteTreeAddNewButton');
                $tab->push($gridFieldLive);
            }
            $fields->insertAfter($tab, 'Main');
        }
    }


    /**
     * Augments (@link Hierarchy::stageChildren()}
     *
     * @param boolean showAll Include all of the elements, even those not shown in the menus.
     *   (only applicable when extension is applied to {@link SiteTree}).
     * @return DataList
     */
    public function stageChildren($showAll = false)
    {
        $baseClass = ClassInfo::baseDataClass($this->owner->class);
        $staged = $baseClass::get()
            ->filter('ParentID', (int)$this->owner->ID)
            ->exclude('ID', (int)$this->owner->ID);
        if (!$showAll && $this->owner->db('ShowInMenus')) {
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
     * Augments (@link Hierarchy::liveChildren()} by hiding excluded child classnames
     *
     * @param boolean $showAll Include all of the elements, even those not shown in the menus.
     *   (only applicable when extension is applied to {@link SiteTree}).
     * @param boolean $onlyDeletedFromStage Only return items that have been deleted from stage
     * @return SS_List
     */
    public function liveChildren($showAll = false, $onlyDeletedFromStage = false)
    {
        $baseClass = ClassInfo::baseDataClass($this->owner->class);
        $children = $baseClass::get()
            ->filter('ParentID', (int)$this->owner->ID)
            ->exclude('ID', (int)$this->owner->ID)
            ->setDataQueryParam(array(
                'Versioned.mode' => $onlyDeletedFromStage ? 'stage_unique' : 'stage',
                'Versioned.stage' => 'Live'
            ));

        if (!$showAll) {
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
        return _t("Lumberjack.TabTitle", "Child Pages");
    }


    /**
     * This returns the gird field config for the lumberjack gridfield.
     *
     * @return GridFieldConfig
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
        return $controller instanceof LeftAndMain
            && in_array($controller->getAction(), array("treeview", "listview", "getsubtree"));
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

        return SiteTree::get()->filter(array(
            'ParentID' => $this->owner->ID,
            'ClassName' => $excluded,
        ));
    }
}
