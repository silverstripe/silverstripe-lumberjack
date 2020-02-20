<?php

namespace SilverStripe\Lumberjack\Forms;

use SilverStripe\Forms\GridField\GridFieldViewButton;
use SilverStripe\View\ArrayData;

class GridFieldSiteTreeViewButton extends GridFieldViewButton
{
    public function getColumnContent($gridField, $record, $columnName)
    {
        if (!$record->canView()) {
            return null;
        }
        
        $data = ArrayData::create([
            'Link' => $record->CMSEditLink(),
        ]);
        
        return $data->renderWith(GridFieldViewButton::class);
    }
}
