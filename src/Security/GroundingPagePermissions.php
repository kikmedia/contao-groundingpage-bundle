<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Security;

final class GroundingPagePermissions
{
    public const ACCESS_MODULE = 'contao_user.modules.grounding_page';

    public const EDIT_ARCHIVE = 'contao_user.groundingPages';

    public const CREATE = 'contao_user.groundingPagep.create';

    public const DELETE = 'contao_user.groundingPagep.delete';
}
