<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

  class LoginController extends AbstractController
  {
      #[Route('/login', name: 'app_login')]

     public function index(AuthenticationUtils $authenticationUtils): Response
      {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
         // get the login error if there is one
         $error = $authenticationUtils->getLastAuthenticationError();

         // last username entered by the user
         $lastUsername = $authenticationUtils->getLastUsername();

          return $this->render('login/index.html.twig', [

             'last_username' => $lastUsername,
             'error'         => $error,
          ]);
      }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    private $passwordEncoder;

    public function __construct(UserPasswordHasherInterface $passwordEncoder)
    {
        $this->passwordEncoder= $passwordEncoder;
    } 

    #[Route(path: '/list', name: 'list')]
    public function list(EntityManagerInterface $entityManager){
        
        $users = $entityManager->getRepository(User::class)->findAll();
        return $this->render('user/index.html.twig',['users'=>$users]);

    }
    #[Route(path: '/user/{id}', name: 'user_show')]
    public function show(int $id,EntityManagerInterface $entityManager){
        
        $user = $entityManager->getRepository(User::class)->find($id);
        return $this->render('user/show.html.twig',['user'=>$user]);

    }
      
    #[Route(path: '/user/delete/{id}', name: 'user_delete')]
    public function delete(int $id,EntityManagerInterface $entityManager){
        
        $user = $entityManager->getRepository(User::class)->find($id);
        $entityManager->remove($user);
        $entityManager->flush();
         return $this->redirectToRoute('app_home');

    }
  }