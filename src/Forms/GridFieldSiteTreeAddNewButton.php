<?php

namespace SilverStripe\Lumberjack\Forms;

use SilverStripe\CMS\Controllers\CMSPageAddController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * This component creates a dropdown of possible page types and a button to create a new page.
 *
 * This bypasses GridFieldDetailForm to use the standard CMS.
 *
 * @package silverstripe
 * @subpackage lumberjack
 *
 * @author Michael Strong <mstrong@silverstripe.org>
 */
class GridFieldSiteTreeAddNewButton extends GridFieldAddNewButton implements GridField_ActionProvider
{
    /**
     * Determine the list of classnames and titles allowed for a given parent object
     *
     * @param SiteTree $parent
     * @return boolean
     */
    public function getAllowedChildren(SiteTree $parent = null)
    {
        if (!$parent || !$parent->canAddChildren()) {
            return array();
        }

        $nonHiddenPageTypes = SiteTree::page_type_classes();
        $allowedChildren = $parent->allowedChildren();
        $children = array();
        foreach ($allowedChildren as $class) {
            if (Config::inst()->get($class, 'show_in_sitetree') === false) {
                $instance = Injector::inst()->get($class);
                // Note: Second argument to SiteTree::canCreate will support inherited permissions
                // post 3.1.12, and will default to the old permission model in 3.1.11 or below
                // See http://docs.silverstripe.org/en/changelogs/3.1.11
                if ($instance->canCreate(null, array('Parent' => $parent)) && in_array($class, $nonHiddenPageTypes)) {
                    $children[$class] = $instance->i18n_singular_name();
                }
            }
        }
        return $children;
    }

    public function getHTMLFragments($gridField)
    {
        $state = $gridField->State->GridFieldSiteTreeAddNewButton;

        $parent = SiteTree::get()->byId(Controller::curr()->currentPageID());

        if ($parent) {
            $state->currentPageID = $parent->ID;
        }

        $children = $this->getAllowedChildren($parent);
        if (empty($children)) {
            return array();
        } elseif (count($children) > 1) {
            $pageTypes = DropdownField::create('PageType', 'Page Type', $children, $parent->defaultChild());
            $pageTypes
                ->setFieldHolderTemplate(__CLASS__ . '_holder')
                ->addExtraClass('gridfield-dropdown no-change-track');

            $state->pageType = $parent->defaultChild();

            if (!$this->buttonName) {
                $this->buttonName = _t(
                    __CLASS__ . '.AddMultipleOptions',
                    'Add new',
                    'Add button text for multiple options.'
                );
            }
        } else {
            $keys = array_keys($children);
            $pageTypes = HiddenField::create('PageType', 'Page Type', $keys[0]);

            $state->pageType = $keys[0];

            if (!$this->buttonName) {
                $this->buttonName = _t(
                    __CLASS__ . '.Add',
                    'Add new {name}',
                    'Add button text for a single option.',
                    ['name' => $children[$keys[0]]]
                );
            }
        }

        $addAction = GridField_FormAction::create($gridField, 'add', $this->buttonName, 'add', 'add');
        $addAction
            ->setAttribute('data-icon', 'add')
            ->addExtraClass('no-ajax btn btn-primary font-icon-plus');

        $forTemplate = ArrayData::create();
        $forTemplate->Fields = ArrayList::create();
        $forTemplate->Fields->push($pageTypes);
        $forTemplate->Fields->push($addAction);

        return [$this->targetFragment => $forTemplate->renderWith(__CLASS__)];
    }

    /**
     * Provide actions to this component.
     *
     * @param  GridField $gridField
     * @return array
    **/
    public function getActions($gridField)
    {
        return array('add');
    }

    /**
     * Handles the add action, but only acts as a wrapper for {@link CMSPageAddController::doAdd()}
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data
     *
     * @return HTTPResponse|null
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'add') {
            $tmpData = json_decode($data['ChildPages']['GridState'], true);
            /** @skipUpgrade  */
            $tmpData = $tmpData['GridFieldSiteTreeAddNewButton'];

            $data = array(
                'ParentID' => $tmpData['currentPageID'],
                'PageType' => $tmpData['pageType']
            );

            /** @var $controller CMSPageAddController */
            $controller = Injector::inst()->create(CMSPageAddController::class);

            // pass current request to newly created controller
            $request = Controller::curr()->getRequest();
            $controller->setRequest($request);

            $form = $controller->AddForm();
            $form->loadDataFrom($data);

            return $controller->doAdd($data, $form);
        }

        return null;
    }
}
