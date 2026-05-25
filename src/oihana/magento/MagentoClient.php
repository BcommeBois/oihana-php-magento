<?php

namespace oihana\magento;

use oihana\magento\traits\MagentoClientTrait;
use oihana\magento\traits\MagentoProductsTrait;

class MagentoClient
{
    use MagentoClientTrait ,
        MagentoProductsTrait ;
}