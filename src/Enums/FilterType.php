<?php

namespace PredatorStudio\LiveTable\Enums;

enum FilterType: string
{
    case TEXT = 'text';
    case SELECT = 'select';
    case DATE = 'date';
}