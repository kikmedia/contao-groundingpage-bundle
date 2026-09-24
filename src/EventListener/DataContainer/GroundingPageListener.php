<?php

declare(strict_types=1);

namespace Grounding\PageBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\Database;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Grounding\PageBundle\Model\GroundingPageModel;
use Symfony\Bundle\SecurityBundle\Security;

class GroundingPageListener extends Backend
{
    public function __construct(private readonly Security $security)
    {
        parent::__construct();
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

    public function resetOnCopy(int $insertId): void
    {
        Database::getInstance()
            ->prepare('UPDATE tl_grounding_page SET published=? WHERE id=?')
            ->execute('', $insertId);
    }

    public function listEntries(array $row): string
    {
        $label = StringUtil::specialchars($row['title']);
        $type  = StringUtil::specialchars($row['entityType'] ?? '');
        $style = $row['published'] ? '' : ' style="color:#b3b3b3"';

        return sprintf(
            '<div class="tl_content_left"%s>%s <span style="color:#999;padding-left:6px">[%s]</span></div>',
            $style,
            $label,
            $type
        );
    }

    public function checkArchivePermission(): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser || $user->isAdmin) {
            return;
        }

        $archiveIds = $this->getPermittedArchiveIds($user);
        $operations = $this->getPermittedOperations($user);

        $GLOBALS['TL_DCA']['tl_grounding_page_archive']['list']['sorting']['root'] = $archiveIds;

        if (!\in_array('create', $operations, true)) {
            $GLOBALS['TL_DCA']['tl_grounding_page_archive']['config']['closed'] = true;
        }

        if (!\in_array('delete', $operations, true)) {
            unset($GLOBALS['TL_DCA']['tl_grounding_page_archive']['list']['operations']['delete']);
        }

        $id = Input::get('id');

        if ($id && Input::get('act') && 'select' !== Input::get('act') && !\in_array((int) $id, $archiveIds, true)) {
            throw new AccessDeniedException(sprintf('Not enough permissions to access grounding page archive ID %s.', $id));
        }
    }

    public function checkPermission(): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser || $user->isAdmin) {
            return;
        }

        $archiveIds = $this->getPermittedArchiveIds($user);

        $GLOBALS['TL_DCA']['tl_grounding_page']['list']['sorting']['root'] = $archiveIds;

        $id = Input::get('id');
        $act = Input::get('act');

        if ($id && $act && 'select' !== $act && 'paste' !== $act) {
            $entry = GroundingPageModel::findById($id);

            if (null !== $entry && !\in_array((int) $entry->pid, $archiveIds, true)) {
                throw new AccessDeniedException(sprintf('Not enough permissions to access grounding page entry ID %s.', $id));
            }
        }
    }

    private function getPermittedArchiveIds(BackendUser $user): array
    {
        $ids = StringUtil::deserialize($user->groundingPages, true);

        foreach ($this->getGroupRows($user, 'groundingPages') as $value) {
            $ids = array_merge($ids, StringUtil::deserialize($value, true));
        }

        return array_unique(array_map('\intval', $ids));
    }

    private function getPermittedOperations(BackendUser $user): array
    {
        $ops = StringUtil::deserialize($user->groundingPagep, true);

        foreach ($this->getGroupRows($user, 'groundingPagep') as $value) {
            $ops = array_merge($ops, StringUtil::deserialize($value, true));
        }

        return array_unique($ops);
    }

    private function getGroupRows(BackendUser $user, string $column): array
    {
        $groupIds = StringUtil::deserialize($user->groups, true);

        if (empty($groupIds)) {
            return [];
        }

        $result = Database::getInstance()->execute(
            sprintf(
                'SELECT %s FROM tl_user_group WHERE id IN (%s)',
                $column,
                implode(',', array_map('\intval', $groupIds))
            )
        );

        return $result->fetchEach($column);
    }
}
