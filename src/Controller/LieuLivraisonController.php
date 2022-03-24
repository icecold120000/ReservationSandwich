<?php

namespace App\Controller;

use App\Entity\LieuLivraison;
use App\Form\LieuLivraisonType;
use App\Repository\LieuLivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/lieu/livraison')]
class LieuLivraisonController extends AbstractController
{
    #[Route('/', name: 'lieu_livraison_index', methods: ['GET'])]
    public function index(LieuLivraisonRepository $lieuLivraisonRepository): Response
    {
        return $this->render('lieu_livraison/index.html.twig', [
            'lieu_livraisons' => $lieuLivraisonRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'lieu_livraison_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lieuLivraison = new LieuLivraison();
        $form = $this->createForm(LieuLivraisonType::class, $lieuLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lieuLivraison);
            $entityManager->flush();

            return $this->redirectToRoute('lieu_livraison_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('lieu_livraison/new.html.twig', [
            'lieu_livraison' => $lieuLivraison,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'lieu_livraison_show', methods: ['GET'])]
    public function show(LieuLivraison $lieuLivraison): Response
    {
        return $this->render('lieu_livraison/show.html.twig', [
            'lieu_livraison' => $lieuLivraison,
        ]);
    }

    #[Route('/{id}/edit', name: 'lieu_livraison_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LieuLivraison $lieuLivraison, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LieuLivraisonType::class, $lieuLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('lieu_livraison_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('lieu_livraison/edit.html.twig', [
            'lieu_livraison' => $lieuLivraison,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'lieu_livraison_delete', methods: ['POST'])]
    public function delete(Request $request, LieuLivraison $lieuLivraison, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lieuLivraison->getId(), $request->request->get('_token'))) {
            $entityManager->remove($lieuLivraison);
            $entityManager->flush();
        }

        return $this->redirectToRoute('lieu_livraison_index', [], Response::HTTP_SEE_OTHER);
    }
}
