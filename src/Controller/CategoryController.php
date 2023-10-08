<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    public function new(Request $request,EntityManagerInterface $em){
        
        $category= new Category();
        
        $form2=$this->createForm(CategoryType::class, $category);
        $form2->handleRequest($request);
        if($form2->isSubmitted() && $form2->isValid()){
            $em->persist ($category);
            $em->flush();
            return $this->redirectToRoute('app_home');
            
        }
        return $this->render('issue/index.html.twig',['form2'=>$form2->createView()]);

    }
}
