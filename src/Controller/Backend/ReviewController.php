<?php

declare(strict_types=1);

namespace Kikmedia\GroundingPageBundle\Controller\Backend;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Date;
use Contao\Message;
use Contao\System;
use Doctrine\DBAL\Connection;
use Kikmedia\GroundingPageBundle\Review\ReviewInput;
use Kikmedia\GroundingPageBundle\Review\ReviewService;
use Kikmedia\GroundingPageBundle\Security\GroundingPagePermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[Autoconfigure(public: true)]
class ReviewController
{
    private const FORM = 'tl_grounding_page_review';

    private const MINOR_FORM = 'tl_grounding_page_review_minor';

    private const DELETE_FORM = 'tl_grounding_page_review_delete';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Environment $twig,
        private readonly Security $security,
        private readonly Connection $connection,
        private readonly ReviewService $reviews,
        private readonly ContaoCsrfTokenManager $tokenManager,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    public function run(): Response|string
    {
        $request = $this->requestStack->getCurrentRequest() ?? throw new \LogicException('There is no current request.');
        $entry = $this->entry($request->query->getInt('id'));
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            throw new AccessDeniedException('Only back end users may open the review protocol.');
        }

        System::loadLanguageFile(self::FORM);
        $labels = $GLOBALS['TL_LANG'][self::FORM];
        $errors = [];
        $submitted = [];

        if ($request->isMethod('POST')) {
            [$errors, $message] = $this->handle($request, (int) $entry['id'], $user);

            if ([] === $errors && null !== $message) {
                Message::addConfirmation($labels[$message]);

                return new RedirectResponse($request->getRequestUri(), Response::HTTP_SEE_OTHER);
            }

            $submitted = $request->request->all();
        }

        return $this->render($request, $entry, $user, $labels, $errors, $submitted);
    }

    private function entry(int $entryId): array
    {
        $entry = $this->connection->fetchAssociative(
            'SELECT e.id, e.pid, e.title, a.reviewerType, a.reviewerName FROM tl_grounding_page e INNER JOIN tl_grounding_page_archive a ON a.id = e.pid WHERE e.id = ?',
            [$entryId]
        );

        if (false === $entry || !$this->security->isGranted(GroundingPagePermissions::EDIT_ARCHIVE, (int) $entry['pid'])) {
            throw new AccessDeniedException(sprintf('Access to the reviews of grounding page entry %d is not allowed.', $entryId));
        }

        return $entry;
    }

    private function handle(Request $request, int $entryId, BackendUser $user): array
    {
        $form = $request->request->getString('FORM_SUBMIT');

        if (self::DELETE_FORM === $form) {
            if (!$user->isAdmin) {
                throw new AccessDeniedException('Only administrators may delete reviews.');
            }

            $this->reviews->delete($entryId, $request->request->getInt('record'));

            return [[], 'deleted'];
        }

        if (self::FORM !== $form && self::MINOR_FORM !== $form) {
            return [[], null];
        }

        if (!$this->security->isGranted(GroundingPagePermissions::REVIEW)) {
            throw new AccessDeniedException('Documenting reviews is not allowed.');
        }

        $text = static fn (string $name): string => trim($request->request->getString($name));
        $fingerprint = $text('fingerprint');

        if (self::MINOR_FORM === $form) {
            return [$this->reviews->confirmMinor($entryId, $text('note'), $fingerprint, (int) $user->id, (string) $user->name), 'minorSaved'];
        }

        $input = new ReviewInput(
            $text('date'),
            $text('reviewerType'),
            $text('reviewerName'),
            $request->request->all('checks'),
            match ($text('result')) {
                'approved' => true,
                'rejected' => false,
                default => null,
            },
            $text('note'),
        );

        return [$this->reviews->document($entryId, $input, $fingerprint, (int) $user->id, (string) $user->name), 'saved'];
    }

    private function render(Request $request, array $entry, BackendUser $user, array $labels, array $errors, array $submitted): string
    {
        $entryId = (int) $entry['id'];
        $state = $this->reviews->state($entryId);
        $last = $this->reviews->lastReview($entryId);
        $form = [
            'date'         => date('Y-m-d'),
            'reviewerType' => $last['reviewerType'] ?? $entry['reviewerType'],
            'reviewerName' => $last['reviewerName'] ?? $entry['reviewerName'],
            'checks'       => [],
            'result'       => '',
            'note'         => '',
        ];
        $minorNote = '';

        if (self::FORM === ($submitted['FORM_SUBMIT'] ?? null)) {
            $form = array_replace($form, array_intersect_key($submitted, $form));

            if (\in_array('content_changed', $errors, true)) {
                $form = array_replace($form, ['checks' => [], 'result' => '']);
            }
        } elseif (self::MINOR_FORM === ($submitted['FORM_SUBMIT'] ?? null)) {
            $minorNote = (string) ($submitted['note'] ?? '');
        }

        return $this->twig->render('@Contao/backend/grounding_page_reviews.html.twig', [
            'labels'       => $labels,
            'title'        => $entry['title'],
            'messages'     => Message::generate(),
            'errors'       => array_map(static fn (string $error): string => $labels['errors'][$error], $errors),
            'rows'         => $this->rows($this->reviews->records($entryId), $state->status->value, $labels),
            'form'         => $form,
            'minorNote'    => $minorNote,
            'checks'       => ReviewInput::CHECKS,
            'types'        => ReviewInput::REVIEWER_TYPES,
            'canReview'    => $this->security->isGranted(GroundingPagePermissions::REVIEW),
            'canConfirm'   => $state->minorAllowed(),
            'canDelete'    => (bool) $user->isAdmin,
            'fingerprint'  => $this->reviews->fingerprint($entryId),
            'requestToken' => $this->tokenManager->getDefaultTokenValue(),
            'action'       => $request->getRequestUri(),
            'backUrl'      => $this->router->generate('contao_backend', ['do' => 'grounding_page', 'table' => 'tl_grounding_page', 'id' => $entry['pid']]),
            'back'         => $GLOBALS['TL_LANG']['MSC']['backBT'] ?? '',
        ]);
    }

    private function rows(array $records, string $latestState, array $labels): array
    {
        $byId = array_column($records, null, 'id');
        $rows = [];

        foreach ($records as $index => $record) {
            $minor = 'minor' === $record['kind'];
            $source = $minor ? $byId[$record['reference']] ?? null : $record;
            $date = null === $source ? false : strtotime((string) $source['reviewDate']);
            $type = (string) ($source['reviewerType'] ?? '');
            $result = $minor ? 'minor' : ($record['approved'] ? 'approved' : 'rejected');

            $rows[] = [
                'id'           => $record['id'],
                'date'         => false === $date ? '' : Date::parse(Config::get('dateFormat'), $date),
                'reviewer'     => null === $source ? '' : sprintf('%s (%s)', $source['reviewerName'], $labels['reviewerTypes'][$type] ?? $type),
                'documentedBy' => sprintf('%s, %s', $record['userName'], Date::parse(Config::get('datimFormat'), (int) $record['tstamp'])),
                'result'       => $labels['results'][$result],
                'state'        => $labels['states'][0 === $index ? $latestState : 'superseded'],
                'note'         => $record['note'],
            ];
        }

        return $rows;
    }
}
