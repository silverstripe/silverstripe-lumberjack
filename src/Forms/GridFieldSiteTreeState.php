<?php

namespace SilverStripe\Lumberjack\Forms;

use SilverStripe\Core\Injector\Injectable;
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
    use Injectable;

    public function augmentColumns($gridField, &$columns)
    {
        // Ensure Actions always appears as the last column.
        $key = array_search('Actions', $columns ?? []);
        if ($key !== false) {
            unset($columns[$key]);
        }

        $columns = array_merge($columns, array(
            'State',
            'Actions',
        ));
    }

    public function getColumnsHandled($gridField)
    {
        return array('State');
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($columnName == 'State') {
            if ($record->hasMethod('isPublished')) {
                $modifiedLabel = '';
                if ($record->isModifiedOnDraft()) {
                    $modifiedLabel = '<span class="modified">' . _t(__CLASS__ . '.Modified', 'Modified') . '</span>';
                }

                $published = $record->isPublished();
                if (!$published) {
                    return '<span class="font-icon-pencil btn--icon-md" aria-hidden="true"></span>' .  _t(
                        __CLASS__ . '.Draft',
                        'Saved as Draft on {date}',
                        'State for when a post is saved.',
                        array(
                            'date' => $record->dbObject('LastEdited')->Nice()
                        )
                    );
                }
                return '<span class="font-icon-check-mark-circle btn--icon-md" aria-hidden="true"></span>' . _t(
                    __CLASS__ . '.Published',
                    'Published on {date}',
                    'State for when a post is published.',
                    array(
                        'date' => $record->dbObject('LastEdited')->Nice()
                    )
                ) . $modifiedLabel;
            }
        }
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        if ($columnName == 'State') {
            if ($record->hasMethod('isPublished')) {
                $published = $record->isPublished();
                if (!$published) {
                    $class = 'gridfield-icon draft';
                } else {
                    $class = 'gridfield-icon published';
                }
                return array('class' => $class);
            }
        }
        return array();
    }

    public function getColumnMetaData($gridField, $columnName)
    {
        switch ($columnName) {
            case 'State':
                return array('title' => _t(__CLASS__ . '.StateTitle', 'State', 'Column title for state'));
            default:
                break;
        }
    }
}
