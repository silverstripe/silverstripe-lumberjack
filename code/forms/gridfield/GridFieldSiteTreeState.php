<?php

use SilverStripe\Forms\GridField\GridField_ColumnProvider;

/**
 * Provides a component to the {@link GridField} which shows the publish status of a page.
 *
 * @package silverstripe
 * @subpackage lumberjack
 *
 * @author Michael Strong <mstrong@silverstripe.org>
**/
class GridFieldSiteTreeState implements GridField_ColumnProvider
{

    public function augmentColumns($gridField, &$columns)
    {
        // Ensure Actions always appears as the last column.
        $key = array_search("Actions", $columns);
        if ($key !== false) {
            unset($columns[$key]);
        }

        $columns = array_merge($columns, array(
            "State",
            "Actions",
        ));
    }

    public function getColumnsHandled($gridField)
    {
        return array("State");
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($columnName == "State") {
            if ($record->hasMethod("isPublished")) {
                $modifiedLabel = "";
                if ($record->isModifiedOnStage) {
                    $modifiedLabel = "<span class='modified'>" . _t("GridFieldSiteTreeState.Modified", "Modified") . "</span>";
                }

                $published = $record->isPublished();
                if (!$published) {
                    return _t(
                        "GridFieldSiteTreeState.Draft",
                        '<i class="btn-icon gridfield-icon btn-icon-pencil"></i> Saved as Draft on {date}',
                        "State for when a post is saved.",
                        array(
                            "date" => $record->dbObject("LastEdited")->Nice()
                        )
                    );
                } else {
                    return _t(
                        "GridFieldSiteTreeState.Published",
                        '<i class="btn-icon gridfield-icon btn-icon-accept"></i> Published on {date}',
                        "State for when a post is published.",
                        array(
                            "date" => $record->dbObject("LastEdited")->Nice()
                        )
                    ) . $modifiedLabel;
                }
            }
        }
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        if ($columnName == "State") {
            if ($record->hasMethod("isPublished")) {
                $published = $record->isPublished();
                if (!$published) {
                    $class = "gridfield-icon draft";
                } else {
                    $class = "gridfield-icon published";
                }
                return array("class" => $class);
            }
        }
        return array();
    }

    public function getColumnMetaData($gridField, $columnName)
    {
        switch ($columnName) {
            case 'State':
                return array("title" => _t("GridFieldSiteTreeState.StateTitle", "State", "Column title for state"));
        }
    }
}
