<?php

namespace Kiosk\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Security extends Constraint
{
    public $message = 'Hibás biztonsági kód';
}
