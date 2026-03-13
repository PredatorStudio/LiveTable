<?php

namespace PredatorStudio\LiveTable\Enums;

enum DateTimeFormat: string
{
    case DMY_HM = 'd.m.Y H:i';
    case DMY_HMS = 'd.m.Y H:i:s';
    case YMD_HM = 'Y-m-d H:i';
    case YMD_HMS = 'Y-m-d H:i:s';
}
