<?php

namespace PredatorStudio\LiveTable\Enums;

enum RowActionsMode: string
{
    /** 3-dot kebab button – clicking opens a dropdown list of actions. */
    case DROPDOWN = 'dropdown';

    /** Inline icon buttons – each action is always visible with a tooltip. */
    case ICONS = 'icons';
}
