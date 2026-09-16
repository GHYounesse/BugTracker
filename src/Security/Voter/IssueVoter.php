<?php

namespace App\Security\Voter;

use App\Entity\Issue;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Repository\ProjectMemberRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Issue>
 */
class IssueVoter extends Voter
{
    public const VIEW = 'ISSUE_VIEW';
    public const EDIT = 'ISSUE_EDIT';
    public const MANAGE = 'ISSUE_MANAGE';

    private const ATTRIBUTES = [self::VIEW, self::EDIT, self::MANAGE];

    public function __construct(private ProjectMemberRepository $projectMembers)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Issue;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /** @var Issue $issue */
        $issue = $subject;
        $project = $issue->getProject();
        if (!$project) {
            return false;
        }

        $membership = $this->projectMembers->findOneForProjectAndUser($project, $user);
        if (!$membership) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT => in_array($membership->getRole(), [ProjectMember::ROLE_ADMIN, ProjectMember::ROLE_MANAGER], true)
                || $issue->getReporter() === $user
                || $issue->getAssigned() === $user,
            self::MANAGE => in_array($membership->getRole(), [ProjectMember::ROLE_ADMIN, ProjectMember::ROLE_MANAGER], true),
            default => false,
        };
    }
}
