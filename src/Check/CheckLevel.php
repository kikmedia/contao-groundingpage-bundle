<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Check;

enum CheckLevel: string
{
    case Required = 'required';
    case Hint = 'hint';
    case Info = 'info';
}
