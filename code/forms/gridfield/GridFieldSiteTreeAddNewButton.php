<?php

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Control\Director;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridField_ActionProvider;

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
class GridFieldSiteTreeAddNewButton extends GridFieldAddNewButton
    implements GridField_ActionProvider
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
            if (Config::inst()->get($class, "show_in_sitetree") === false) {
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
            $pageTypes = DropdownField::create("PageType", "Page Type", $children, $parent->defaultChild());
            $pageTypes->setFieldHolderTemplate("GridFieldSiteTreeAddNewButton_holder")->addExtraClass("gridfield-dropdown no-change-track");

            $state->pageType = $parent->defaultChild();

            if (!$this->buttonName) {
                $this->buttonName = _t('GridFieldSiteTreeAddNewButton.AddMultipleOptions', 'Add new', "Add button text for multiple options.");
            }
        } else {
            $keys = array_keys($children);
            $pageTypes = HiddenField::create('PageType', 'Page Type', $keys[0]);

            $state->pageType = $keys[0];

            if (!$this->buttonName) {
                $this->buttonName = _t('GridFieldSiteTreeAddNewButton.Add', 'Add new {name}', 'Add button text for a single option.', array($children[$keys[0]]));
            }
        }

        $addAction = new GridField_FormAction($gridField, 'add', $this->buttonName, 'add', 'add');
        $addAction->setAttribute('data-icon', 'add')->addExtraClass("no-ajax ss-ui-action-constructive dropdown-action");

        $forTemplate = new ArrayData(array());
        $forTemplate->Fields = new ArrayList();
        $forTemplate->Fields->push($pageTypes);
        $forTemplate->Fields->push($addAction);

        Requirements::css(LUMBERJACK_DIR . "/css/lumberjack.css");
        Requirements::javascript(LUMBERJACK_DIR . "/javascript/GridField.js");

        return array($this->targetFragment => $forTemplate->renderWith("GridFieldSiteTreeAddNewButton"));
    }



    /**
     * Provide actions to this component.
     *
     * @param GridField $gridField
     * @return array
    **/
    public function getActions($gridField)
    {
        return array("add");
    }



    /**
     * Handles the add action, but only acts as a wrapper for {@link CMSPageAddController::doAdd()}
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data
    **/
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == "add") {
            $tmpData = json_decode($data['ChildPages']['SilverStripe\\Forms\\GridField\\GridState'], true);
            $tmpData = $tmpData['GridFieldSiteTreeAddNewButton'];

            $data = array(
                "ParentID" => $tmpData['currentPageID'],
                "PageType" => $tmpData['pageType']
            );

            $controller = Injector::inst()->create("SilverStripe\\CMS\\Controllers\\CMSPageAddController");

            $form = $controller->AddForm();
            $form->loadDataFrom($data);

            $controller->doAdd($data, $form);
            $response = $controller->getResponseNegotiator()->getResponse();

            // Get the current record
            $record = SiteTree::get()->byId($controller->currentPageID());
            if ($record) {
                $response->redirect(Director::absoluteBaseURL() . $record->CMSEditLink(), 301);
            }
            return $response;
        }
    }
}
