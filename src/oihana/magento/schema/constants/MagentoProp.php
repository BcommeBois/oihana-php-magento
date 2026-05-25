<?php

namespace oihana\magento\schema\constants;

use oihana\magento\schema\constants\traits\MediaGalleryEntryTrait;
use oihana\magento\schema\constants\traits\MediaGalleryInterfaceTrait;
use oihana\magento\schema\constants\traits\ProductTrait;
use oihana\magento\schema\constants\traits\ThingTrait;
use oihana\reflect\traits\ConstantsTrait;

class MagentoProp
{
    use ConstantsTrait ,

        ThingTrait ,
        MediaGalleryInterfaceTrait,
        MediaGalleryEntryTrait ,
        ProductTrait ;
}