<?php

namespace App\Controller;

use App\Entity\LimitationCommande;
use App\Form\LimitationCommandeType;
use App\Repository\LimitationCommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/limite")
 */
class LimiteController extends AbstractController
{
    /**
     * @Route("/", name="limite_index", methods={"GET"})
     */
    public function index(LimitationCommandeRepository $limitationCommandeRepository): Response
    {
        return $this->render('limite/index.html.twig', [
            'limitations' => $limitationCommandeRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="limite_new", methods={"GET", "POST"})
     * À Garder pour les développeurs
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $limitationCommande = new LimitationCommande();
        $form = $this->createForm(LimitationCommandeType::class, $limitationCommande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($limitationCommande);
            $entityManager->flush();
            $this->addFlash(
                'SuccessLimite',
                'Votre limitation a été sauvegardée !'
            );
            return $this->redirectToRoute('limite_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('limite/new.html.twig', [
            'limitation_commande' => $limitationCommande,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/delete_view", name="limite_delete_view", methods={"GET"})
     * À Garder pour les développeurs
     */
    public function delete_view(LimitationCommande $limitationCommande): Response
    {
        return $this->render('limite/delete_view.html.twig', [
            'limitation' => $limitationCommande,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="limite_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, LimitationCommande $limitationCommande, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LimitationCommandeType::class, $limitationCommande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash(
                'SuccessLimite',
                'Votre limitation a été modifiée !'
            );

            return $this->redirectToRoute('limite_edit', ['id' => $limitationCommande->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('limite/edit.html.twig', [
            'limitation_commande' => $limitationCommande,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="limite_delete", methods={"POST"})
     * À Garder pour les développeurs
     */
    public function delete(Request $request, LimitationCommande $limitationCommande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $limitationCommande->getId(), $request->request->get('_token'))) {
            $entityManager->remove($limitationCommande);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteEleve',
                'Votre limitation a été supprimée !'
            );
        }

        return $this->redirectToRoute('limite_index', [], Response::HTTP_SEE_OTHER);
    }
}
