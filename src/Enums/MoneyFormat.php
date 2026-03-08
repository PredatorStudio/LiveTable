<?php

namespace PredatorStudio\LiveTable\Enums;

enum MoneyFormat: string
{
    case SPACE_DOT     = 'space_dot';
    case NOSPACE_DOT   = 'nospace_dot';
    case SPACE_COMMA   = 'space_comma';
    case NOSPACE_COMMA = 'nospace_comma';
}
