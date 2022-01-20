<?php

namespace App\Controller;

use App\Entity\Sandwich;
use App\Form\SandwichType;
use App\Repository\SandwichRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/sandwich")
 */
class SandwichController extends AbstractController
{
    /**
     * @Route("/", name="sandwich_index", methods={"GET"})
     */
    public function index(SandwichRepository $sandwichRepository): Response
    {
        return $this->render('sandwich/index.html.twig', [
            'sandwiches' => $sandwichRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="sandwich_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sandwich = new Sandwich();
        $form = $this->createForm(SandwichType::class, $sandwich);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sandwich);
            $entityManager->flush();

            return $this->redirectToRoute('sandwich_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sandwich/new.html.twig', [
            'sandwich' => $sandwich,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="sandwich_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Sandwich $sandwich, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SandwichType::class, $sandwich);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('sandwich_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sandwich/edit.html.twig', [
            'sandwich' => $sandwich,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="sandwich_delete", methods={"POST"})
     */
    public function delete(Request $request, Sandwich $sandwich, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sandwich->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sandwich);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sandwich_index', [], Response::HTTP_SEE_OTHER);
    }
}
