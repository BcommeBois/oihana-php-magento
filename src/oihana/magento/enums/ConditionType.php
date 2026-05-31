<?php

namespace oihana\magento\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Defines the possible values for Magento filter condition types.
 * Useful to avoid hardcoding strings like 'eq', 'like', etc.
 */
class ConditionType
{
    use ConstantsTrait ;

    /**
     * Greater than
     */
    public const string GT = 'gt';

    /**
     * Greater than or equal
     */
    public const string GTEQ = 'gteq';

    /**
     * In array
     */
    public const string IN = 'in';

    /**
     * Is not null
     */
    public const string NOT_NULL = 'notnull';

    /**
     * Not equal
     */
    public const string NEQ = 'neq';

    /**
     * Not in array
     */
    public const string NIN = 'nin';

    /**
     * Not like
     */
    public const string NLIKE = 'nlike';

    /**
     * Less than
     */
    public const string LT = 'lt';

    /**
     * Less than or equal
     */
    public const string LTEQ = 'lteq';

    /**
     * Equal
     */
    public const string EQ = 'eq';

    /**
     * Is null
     */
    public const string NULL = 'null';

    /**
     * Like (SQL LIKE)
     */
    public const string LIKE = 'like';
}