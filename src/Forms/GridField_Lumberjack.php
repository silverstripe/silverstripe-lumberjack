<?php

namespace SilverStripe\Lumberjack\Forms;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldViewButton;

class GridField_Lumberjack extends GridField
{
    public function performReadonlyTransformation()
    {
        $copy = parent::performReadonlyTransformation();
        
        $copyConfig = $copy->getConfig();
        if ($copyConfig->getComponentByType(GridFieldViewButton::class)) {
            $copyConfig->removeComponentsByType(GridFieldViewButton::class);
            $copyConfig->addComponents(new GridFieldSiteTreeViewButton());
        }
        
        return $copy;
    }
}
