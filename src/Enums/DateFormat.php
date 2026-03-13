<?php

namespace PredatorStudio\LiveTable\Enums;

enum DateFormat: string
{
    case DMY = 'd.m.Y';
    case YMD = 'Y-m-d';
    case MDY = 'm/d/Y';
    case DM = 'd.m';
}
