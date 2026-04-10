<?php

namespace PredatorStudio\LiveTable\Enums;

enum FilterType: string
{
    case TEXT           = 'text';
    case SELECT         = 'select';
    case DATE           = 'date';
    case NUMBER         = 'number';
    case NUMBER_RANGE   = 'number_range';
    case DATE_RANGE     = 'date_range';
    case DATETIME       = 'datetime';
    case DATETIME_RANGE = 'datetime_range';
    case TIME           = 'time';
    case BOOLEAN        = 'boolean';
    case MONEY          = 'money';
}
