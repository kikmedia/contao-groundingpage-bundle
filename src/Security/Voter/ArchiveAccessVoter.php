<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Security\Voter;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Kikmedia\GroundingPageBundle\Security\GroundingPagePermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class ArchiveAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function getTable(): string
    {
        return 'tl_grounding_page_archive';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if (!$this->accessDecisionManager->decide($token, [GroundingPagePermissions::ACCESS_MODULE])) {
            return false;
        }

        if ($action instanceof CreateAction) {
            return $this->accessDecisionManager->decide($token, [GroundingPagePermissions::CREATE]);
        }

        if (!$this->accessDecisionManager->decide($token, [GroundingPagePermissions::EDIT_ARCHIVE], (int) $action->getCurrentId())) {
            return false;
        }

        return !$action instanceof DeleteAction
            || $this->accessDecisionManager->decide($token, [GroundingPagePermissions::DELETE]);
    }
}
