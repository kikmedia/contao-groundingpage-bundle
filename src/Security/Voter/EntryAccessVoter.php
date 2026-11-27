<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Security\Voter;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Contao\CoreBundle\Security\Voter\DataContainer\ParentAccessTrait;
use Kikmedia\GroundingPageBundle\Security\GroundingPagePermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EntryAccessVoter extends AbstractDataContainerVoter
{
    use ParentAccessTrait;

    protected function getTable(): string
    {
        return 'tl_grounding_page';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if (
            !$this->accessDecisionManager->decide($token, [GroundingPagePermissions::ACCESS_MODULE])
            || !$this->hasAccessToParent($token, GroundingPagePermissions::EDIT_ARCHIVE, $action)
        ) {
            return false;
        }

        return !$action instanceof DeleteAction
            || $this->accessDecisionManager->decide($token, [GroundingPagePermissions::DELETE]);
    }
}
