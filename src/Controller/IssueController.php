<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Issue;
use App\Entity\Project;
use App\Form\CommentType;
use App\Form\IssueType;
use App\Form\ProjectType;
use App\Security\Voter\IssueVoter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class IssueController extends AbstractController
{
    #[Route('/issue', name: 'app_issue')]
    public function new(Request $request,EntityManagerInterface $em, SluggerInterface $slugger)
    {
        if($this->getUser()){
        
        $issue= new Issue();
        // set before validation runs: reporter has a NotNull constraint, so
        // an unpopulated Issue would always fail validation otherwise
        $issue->setReporter($this->getUser());
        $form=$this->createForm(IssueType::class, $issue, ['user' => $this->getUser()]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {

             /** @var UploadedFile $attachmentFile */
             $attachmentFile = $form->get('attachment')->getData();
             if ($attachmentFile)
            {
                 $originalFilename = pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME);
                 $safeFilename = $slugger->slug($originalFilename);
                 $newFilename = $safeFilename.'-'.uniqid().'.'.$attachmentFile->guessExtension();
                 try {
                    $attachmentFile->move(
                         $this->getParameter('kernel.project_dir').'/public/uploads',
                         $newFilename
                     );
                 } catch (FileException $e) {
                 }
                $issue->setAttachment($newFilename);

            }
            $issue->setSubmittedAt(new \DateTime());
                $issue->setUpdatedAt(new \DateTime());
                $em->persist ($issue);
                $em->flush();
                return $this->redirectToRoute('app_home');
        }
            $project= new Project();
            $form3=$this->createForm(ProjectType::class, $project);
            $form3->handleRequest($request);
            if($form3->isSubmitted() && $form3->isValid())
            {
                $em->persist ($project);
                $em->flush();
                return $this->redirectToRoute('app_home');
                
            }
        return $this->render('issue/new.html.twig',['form'=>$form->createView(),'form3'=>$form3->createView()]);
    }
    else{
        return $this->redirectToRoute('app_login');
    }
    }


    #[Route('/', name: 'app_home')]
    public function index(Request $request,EntityManagerInterface $entityManager)
    {
        if($this->getUser()){
         $issueRepository = $entityManager->getRepository(Issue::class);
         if ($this->isGranted('ROLE_ADMIN')) {
             $newIssues = $issueRepository->findNew();
             $processedIssues = $issueRepository->findProcessed();
             $acceptedIssues = $issueRepository->findAccepted();
         } else {
             $newIssues = $issueRepository->findNewForUser($this->getUser());
             $processedIssues = $issueRepository->findProcessedForUser($this->getUser());
             $acceptedIssues = $issueRepository->findAcceptedForUser($this->getUser());
         }
         return $this->render('issue/index.html.twig',['newIssues'=>$newIssues ,'processedIssues'=>$processedIssues,'acceptedIssues'=> $acceptedIssues ]);
        }
        else{
        return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/issue/{id}', name: 'issue_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $issue = $em->getRepository(Issue::class)->find($id);
        if (!$issue) {
            throw new NotFoundHttpException('Issue not found.');
        }

        $this->denyAccessUnlessGranted(IssueVoter::VIEW, $issue);

        $commentForm = $this->createForm(CommentType::class, new Comment());

        return $this->render('issue/show.html.twig', [
            'issue' => $issue,
            'commentForm' => $commentForm->createView(),
        ]);
    }

    #[Route('/issue/{id}/edit', name: 'issue_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $issue = $em->getRepository(Issue::class)->find($id);
        if (!$issue) {
            throw new NotFoundHttpException('Issue not found.');
        }

        $this->denyAccessUnlessGranted(IssueVoter::EDIT, $issue);

        $form = $this->createForm(IssueType::class, $issue, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $attachmentFile */
            $attachmentFile = $form->get('attachment')->getData();
            if ($attachmentFile) {
                $originalFilename = pathinfo($attachmentFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$attachmentFile->guessExtension();
                try {
                    $attachmentFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads',
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $issue->setAttachment($newFilename);
            }

            $issue->setUpdatedAt(new \DateTime());
            $em->flush();

            return $this->redirectToRoute('issue_show', ['id' => $issue->getId()]);
        }

        return $this->render('issue/edit.html.twig', [
            'issue' => $issue,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/issue/{id}/comment', name: 'issue_comment', methods: ['POST'])]
    public function addComment(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $issue = $em->getRepository(Issue::class)->find($id);
        if (!$issue) {
            throw new NotFoundHttpException('Issue not found.');
        }

        $this->denyAccessUnlessGranted(IssueVoter::VIEW, $issue);

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setAuthor($this->getUser());
            $comment->setIssue($issue);
            $comment->setCreatedAt(new \DateTime());
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('issue_show', ['id' => $id]);
        }

        return $this->render('issue/show.html.twig', [
            'issue' => $issue,
            'commentForm' => $form->createView(),
        ]);
    }
}