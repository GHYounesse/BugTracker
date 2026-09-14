<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Issue;
use App\Entity\Project;
use App\Form\CategoryType;
use App\Form\IssueType;
use App\Form\ProjectType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class IssueController extends AbstractController
{
    #[Route('/issue', name: 'app_issue')]
    public function new(Request $request,EntityManagerInterface $em, SluggerInterface $slugger)
    {
        if($this->getUser()){
        
        $issue= new Issue();
        $form=$this->createForm(IssueType::class, $issue);
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
                $issue->setReporter($this->getUser());
                $em->persist ($issue);
                $em->flush();
                return $this->redirectToRoute('app_home');
        }
            $category= new Category();
            $form2=$this->createForm(CategoryType::class, $category);
            $form2->handleRequest($request);
            if($form2->isSubmitted() && $form2->isValid())
            {
                $em->persist ($category);
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
        return $this->render('issue/new.html.twig',['form'=>$form->createView(),'form2'=>$form2->createView(),'form3'=>$form3->createView()]);
    }
    else{
        return $this->redirectToRoute('app_login');
    }
    }


    #[Route('/', name: 'app_home')]
    public function index(Request $request,EntityManagerInterface $entityManager)
    {
        if($this->getUser()){
         $newIssues = $entityManager->getRepository(Issue::class)->findNew();
         $processedIssues = $entityManager->getRepository(Issue::class)->findProcessed();
         $acceptedIssues = $entityManager->getRepository(Issue::class)->findAccepted();
         return $this->render('issue/index.html.twig',['newIssues'=>$newIssues ,'processedIssues'=>$processedIssues,'acceptedIssues'=> $acceptedIssues ]);
        }
        else{
        return $this->redirectToRoute('app_login');
        }
    }
}