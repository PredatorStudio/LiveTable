<?php

namespace PredatorStudio\LiveTable\ValueObjects;

readonly class FieldDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public array  $options = [],
    ) {}
}