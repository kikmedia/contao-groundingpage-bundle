<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\Database;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;
use Kikmedia\GroundingPageBundle\Security\GroundingPagePermissions;
use Symfony\Bundle\SecurityBundle\Security;

class GroundingPageListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function generateAlias(string $varValue, DataContainer $dc): string
    {
        $aliasExists = static function (string $alias) use ($dc): bool {
            $result = Database::getInstance()
                ->prepare('SELECT id FROM tl_grounding_page WHERE alias=? AND id!=?')
                ->execute($alias, $dc->id);

            return $result->numRows > 0;
        };

        if ('' === $varValue) {
            $varValue = System::getContainer()
                ->get('contao.slug')
                ->generate($dc->activeRecord->title ?? '', 0, $aliasExists);
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new \RuntimeException(sprintf('Invalid alias "%s" (reserved numeric alias)', $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new \RuntimeException(sprintf('Alias "%s" already exists', $varValue));
        }

        return $varValue;
    }

    public function restrictArchives(): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser || $user->isAdmin) {
            return;
        }

        $archiveIds = array_map('\intval', StringUtil::deserialize($user->groundingPages, true));
        $GLOBALS['TL_DCA']['tl_grounding_page_archive']['list']['sorting']['root'] = $archiveIds ?: [0];

        if (!$this->security->isGranted(GroundingPagePermissions::CREATE)) {
            $GLOBALS['TL_DCA']['tl_grounding_page_archive']['config']['closed'] = true;
            $GLOBALS['TL_DCA']['tl_grounding_page_archive']['config']['notCreatable'] = true;
            $GLOBALS['TL_DCA']['tl_grounding_page_archive']['config']['notCopyable'] = true;
        }

        $this->restrictDeletion('tl_grounding_page_archive');
    }

    public function restrictEntries(): void
    {
        $this->restrictDeletion('tl_grounding_page');
    }

    private function restrictDeletion(string $table): void
    {
        if (!$this->security->isGranted(GroundingPagePermissions::DELETE)) {
            $GLOBALS['TL_DCA'][$table]['config']['notDeletable'] = true;
        }
    }
}
