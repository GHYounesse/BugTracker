<?php

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Repository\ProjectMemberRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Project>
 */
class ProjectVoter extends Voter
{
    public const VIEW = 'PROJECT_VIEW';
    public const EDIT = 'PROJECT_EDIT';
    public const MANAGE_MEMBERS = 'PROJECT_MANAGE_MEMBERS';
    public const DELETE = 'PROJECT_DELETE';

    private const ATTRIBUTES = [self::VIEW, self::EDIT, self::MANAGE_MEMBERS, self::DELETE];

    public function __construct(private ProjectMemberRepository $projectMembers)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Project;
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

        /** @var Project $project */
        $project = $subject;
        $membership = $this->projectMembers->findOneForProjectAndUser($project, $user);
        if (!$membership) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT => in_array($membership->getRole(), [ProjectMember::ROLE_ADMIN, ProjectMember::ROLE_MANAGER], true),
            self::MANAGE_MEMBERS, self::DELETE => $membership->getRole() === ProjectMember::ROLE_ADMIN,
            default => false,
        };
    }
}
