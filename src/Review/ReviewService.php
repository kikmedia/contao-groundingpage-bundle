<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Review;

use FOS\HttpCache\CacheInvalidator;

class ReviewService
{
    private const STANDARD_VERSION = '1.7';

    public function __construct(
        private readonly ReviewRepository $repository,
        private readonly EntryContentLoader $loader,
        private readonly CacheInvalidator|null $cacheManager = null,
    ) {
    }

    public function state(int $entryId): ReviewState
    {
        $records = $this->records($entryId);

        return ReviewEvaluator::evaluate($records, [] === $records ? '' : $this->fingerprint($entryId));
    }

    public function fingerprint(int $entryId): string
    {
        $content = $this->loader->load($entryId);

        return Fingerprint::of($content['entry'], $content['elements']);
    }

    public function hasHiddenContent(int $entryId): bool
    {
        return Fingerprint::hasHiddenElements($this->loader->load($entryId)['elements']);
    }

    public function records(int $entryId): array
    {
        return $this->repository->findByEntry($entryId);
    }

    public function lastReview(int $entryId): array|null
    {
        foreach ($this->records($entryId) as $record) {
            if ('review' === $record['kind']) {
                return $record;
            }
        }

        return null;
    }

    public function document(int $entryId, ReviewInput $input, string $expectedFingerprint, int $userId, string $userName): array
    {
        $fingerprint = $this->fingerprint($entryId);
        $errors = $input->errors(date('Y-m-d'), $this->lastReview($entryId)['reviewDate'] ?? null);

        if ($fingerprint !== $expectedFingerprint) {
            $errors[] = 'content_changed';
        }

        if ([] !== $errors) {
            return $errors;
        }

        $this->save($entryId, $fingerprint, $userId, $userName, [
            'kind'         => 'review',
            'reviewDate'   => $input->date,
            'reviewerType' => $input->reviewerType,
            'reviewerName' => trim($input->reviewerName),
            'approved'     => $input->approved ? 1 : 0,
            'checks'       => implode(',', array_intersect(ReviewInput::CHECKS, $input->checks)),
            'note'         => trim($input->note),
        ]);

        return [];
    }

    public function confirmMinor(int $entryId, string $note, string $expectedFingerprint, int $userId, string $userName): array
    {
        $fingerprint = $this->fingerprint($entryId);
        $state = ReviewEvaluator::evaluate($this->records($entryId), $fingerprint);

        if ($fingerprint !== $expectedFingerprint) {
            return ['content_changed'];
        }

        if (!$state->minorAllowed()) {
            return ['minor_not_allowed'];
        }

        if ('' === trim($note)) {
            return ['note_required'];
        }

        $this->save($entryId, $fingerprint, $userId, $userName, [
            'kind'      => 'minor',
            'reference' => $state->approvedId,
            'approved'  => 0,
            'note'      => trim($note),
        ]);

        return [];
    }

    public function delete(int $entryId, int $recordId): void
    {
        $this->repository->delete($recordId, $entryId);
        $this->invalidate($entryId);
    }

    private function save(int $entryId, string $fingerprint, int $userId, string $userName, array $record): void
    {
        $this->repository->insert($record + [
            'pid'             => $entryId,
            'tstamp'          => time(),
            'userId'          => $userId,
            'userName'        => $userName,
            'standardVersion' => self::STANDARD_VERSION,
            'fingerprint'     => $fingerprint,
        ]);

        $this->invalidate($entryId);
    }

    private function invalidate(int $entryId): void
    {
        $this->cacheManager?->invalidateTags(['contao.db.tl_grounding_page.' . $entryId]);
    }
}
