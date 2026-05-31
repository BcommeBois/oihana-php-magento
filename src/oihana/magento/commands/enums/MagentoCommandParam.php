<?php

namespace oihana\magento\commands\enums;

use oihana\commands\enums\traits\CommandParamTrait;
use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the Magento command parameters.
 *
 * Aggregates the common command parameters from {@see CommandParamTrait}
 * (`BATCH_SIZE`, `DESCRIPTION`, `HELP`, …) and adds Magento-specific
 * keys (`MAGENTO`) plus the document-oriented keys (`DOCUMENTS`, `FIELDS`,
 * `REMOVE_KEYS`).
 */
class MagentoCommandParam
{
    use ConstantsTrait,
        CommandParamTrait ;

    /**
     * The 'documents' parameter.
     */
    public const string DOCUMENTS = 'documents' ;

    /**
     * The 'fields' parameter.
     */
    public const string FIELDS = 'fields' ;

    /**
     * The 'magento' parameter.
     */
    public const string MAGENTO = 'magento' ;

    /**
     * The 'removeKeys' parameter.
     */
    public const string REMOVE_KEYS = 'removeKeys' ;
}
