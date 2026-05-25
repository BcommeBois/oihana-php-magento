<?php

namespace oihana\magento\commands\enums;

use oihana\arango\commands\enums\traits\DocumentsCommandParamTrait;

/**
 * The trait to defines the constants of the Magento commands.
 */
class MagentoCommandParam
{
    use DocumentsCommandParamTrait;

    /**
     * The 'magento' parameter.
     */
    public const string MAGENTO = 'magento' ;
}