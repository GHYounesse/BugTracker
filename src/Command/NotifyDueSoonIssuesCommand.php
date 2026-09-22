<?php

namespace App\Command;

use App\Entity\Issue;
use App\Entity\Notification;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Notifies the assignee (or, if unassigned, the reporter) of any open issue
 * whose due date is within the given window - including one that has already
 * passed, so an issue that became overdue between runs still gets flagged.
 *
 * Meant to run on a schedule (e.g. hourly, via cron or Windows Task
 * Scheduler); this repo does not configure one, so it needs to be wired up
 * on the deployment. Each issue is only ever notified once per due date:
 * issue.dueSoonNotifiedAt is set after notifying, and is cleared by
 * IssueController::edit() whenever the due date itself changes, so a
 * rescheduled issue can be flagged again for its new date.
 */
#[AsCommand(name: 'app:notify-due-soon', description: 'Notify assignees/reporters about issues due within the given window')]
class NotifyDueSoonIssuesCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private NotificationService $notifier)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('hours', null, InputOption::VALUE_REQUIRED, 'How many hours ahead counts as "due soon"', '24')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $hours = (int) $input->getOption('hours');
        if ($hours < 1) {
            $io->error('--hours must be at least 1.');

            return Command::INVALID;
        }

        $horizon = (new \DateTime())->modify(sprintf('+%d hours', $hours));

        /** @var Issue[] $issues */
        $issues = $this->em->getRepository(Issue::class)->createQueryBuilder('i')
            ->andWhere('i.dueDate IS NOT NULL')
            ->andWhere('i.dueDate <= :horizon')
            ->andWhere('i.status != :closed')
            ->andWhere('i.dueSoonNotifiedAt IS NULL')
            ->setParameter('horizon', $horizon)
            ->setParameter('closed', 'closed')
            ->getQuery()
            ->getResult();

        $now = new \DateTime();
        $notified = 0;
        foreach ($issues as $issue) {
            $recipient = $issue->getAssigned() ?? $issue->getReporter();
            if (!$recipient) {
                continue;
            }

            $this->notifier->notify($recipient, Notification::TYPE_DUE_SOON, $issue);
            $issue->setDueSoonNotifiedAt($now);
            ++$notified;
        }

        $this->em->flush();

        $io->writeln(sprintf('Notified about %d issue(s) due within %d hour(s) (of %d candidate(s)).', $notified, $hours, count($issues)));

        return Command::SUCCESS;
    }
}
