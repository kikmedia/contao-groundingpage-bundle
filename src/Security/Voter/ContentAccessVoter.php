<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Security\Voter;

use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDynamicPtableVoter;
use Doctrine\DBAL\Connection;
use Kikmedia\GroundingPageBundle\Security\GroundingPagePermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class ContentAccessVoter extends AbstractDynamicPtableVoter
{
    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
        parent::__construct($connection);
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool
    {
        if ('tl_grounding_page' !== $table) {
            return true;
        }

        if (!$this->accessDecisionManager->decide($token, [GroundingPagePermissions::ACCESS_MODULE])) {
            return false;
        }

        $archiveId = (int) $this->connection->fetchOne('SELECT pid FROM tl_grounding_page WHERE id = ?', [$id]);

        return $archiveId
            && $this->accessDecisionManager->decide($token, [GroundingPagePermissions::EDIT_ARCHIVE], $archiveId);
    }
}
