<?php

namespace SilverStripe\Lumberjack\Forms;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\ORM\DataObject;
use SilverStripe\Model\ArrayData;

/**
 * Swaps the GridField Link out for the SiteTree edit link using {@link SiteTree::CMSEditLink()}.
 *
 * Bypasses GridFieldDetailForm
 *
 * @author Michael Strong <mstrong@silverstripe.org>
 **/
class GridFieldSiteTreeEditButton extends GridFieldEditButton
{
    /**
     * @param  GridField $gridField
     * @param  DataObject $record
     * @param  string $columnName
     * @return string - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        // No permission checks - handled through GridFieldDetailForm
        // which can make the form readonly if no edit permissions are available.

        $data = ArrayData::create([
            'Link' => $record->getCMSEditLink(),
            'ExtraClass' => $this->getExtraClass(),
        ]);

        return $data->renderWith(GridFieldEditButton::class);
    }
}
