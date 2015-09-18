<?php

namespace Kiosk\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SecurityValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (sha1($value) != '507f5d293c27f3cea349f308a0deeb543e496e0a') {
            $this->context->addViolation($constraint->message);
        }
    }
}
