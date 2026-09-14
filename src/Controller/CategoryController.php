<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'category_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/category/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', sprintf('Category "%s" created.', $category->getName()));

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/{id}/edit', name: 'category_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->find($id);
        if (!$category) {
            throw new NotFoundHttpException('Category not found.');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', sprintf('Category "%s" updated.', $category->getName()));

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/{id}/delete', name: 'category_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): RedirectResponse
    {
        $category = $em->getRepository(Category::class)->find($id);
        if (!$category) {
            throw new NotFoundHttpException('Category not found.');
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete-category-'.$id, $request->request->get('_token')))) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        if (!$category->getIssues()->isEmpty()) {
            $this->addFlash('error', sprintf(
                'Cannot delete "%s" - %d issue(s) still use this category.',
                $category->getName(),
                $category->getIssues()->count()
            ));

            return $this->redirectToRoute('category_index');
        }

        $em->remove($category);
        $em->flush();

        $this->addFlash('success', 'Category deleted.');

        return $this->redirectToRoute('category_index');
    }
}
