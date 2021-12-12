<?php

namespace Application\Forms\Filters;


use Laminas\Filter\AbstractFilter;

class CommaReplacement extends AbstractFilter {

    public function filter($value) {
        if (!is_scalar($value)) {
            return NULL;
        }
        $value = (string) $value;
        $value = preg_replace('#,#', '.', $value);
        $value = (string) $value;
        return $value;
    }
}
