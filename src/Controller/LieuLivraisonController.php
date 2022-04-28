<?php

namespace App\Controller;

use App\Entity\LieuLivraison;
use App\Form\FilterOrSearch\FilterLieuType;
use App\Form\LieuLivraisonType;
use App\Repository\CommandeGroupeRepository;
use App\Repository\LieuLivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/lieu/livraison")
 */
class LieuLivraisonController extends AbstractController
{
    /**
     * @Route("/", name="lieu_livraison_index", methods={"GET","POST"})
     */
    public function index(LieuLivraisonRepository $lieuLivraisonRepo,
                          PaginatorInterface      $paginator, Request $request): Response
    {
        $lieux = $lieuLivraisonRepo->findAll();

        $form = $this->createForm(FilterLieuType::class);
        $search = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lieux = $lieuLivraisonRepo->filter(
                $search->get('lieuActive')->getData(),
                $search->get('ordreLieu')->getData()
            );
        }

        $lieux = $paginator->paginate(
            $lieux,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('lieu_livraison/index.html.twig', [
            'lieu_livraisons' => $lieux,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="lieu_livraison_new", methods={"GET","POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lieuLivraison = new LieuLivraison();
        $form = $this->createForm(LieuLivraisonType::class, $lieuLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lieuLivraison);
            $entityManager->flush();

            $this->addFlash(
                'SuccessLieu',
                'Le lieu de livraison a été sauvegardé !'
            );

            return $this->redirectToRoute('lieu_livraison_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('lieu_livraison/new.html.twig', [
            'lieu_livraison' => $lieuLivraison,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/delete_view", name="lieu_livraison_delete_view", methods={"GET","POST"})
     */
    public function delete_view(LieuLivraison $lieuLivraison): Response
    {
        return $this->render('lieu_livraison/delete_view.html.twig', [
            'lieu_livraison' => $lieuLivraison,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="lieu_livraison_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, LieuLivraison $lieuLivraison, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LieuLivraisonType::class, $lieuLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'SuccessLieu',
                'Le lieu de livraison a été modifiée !'
            );

            return $this->redirectToRoute('lieu_livraison_edit', ['id' => $lieuLivraison->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('lieu_livraison/edit.html.twig', [
            'lieu_livraison' => $lieuLivraison,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="lieu_livraison_delete", methods={"POST"})
     */
    public function delete(Request                  $request, LieuLivraison $lieuLivraison, EntityManagerInterface $entityManager,
                           CommandeGroupeRepository $comGroupeRepo, LieuLivraisonRepository $lieuRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $lieuLivraison->getId(), $request->request->get('_token'))) {

            $commandesGroupe = $comGroupeRepo->findByLieuLivraison($lieuLivraison->getId());

            foreach ($commandesGroupe as $commandeGroupe) {
                $commandeGroupe->setLieuLivraison($lieuRepository->find(['id' => 1]));
            }

            $entityManager->remove($lieuLivraison);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteLieu',
                'Le lieu a été supprimé !'
            );
        }

        return $this->redirectToRoute('lieu_livraison_index', [], Response::HTTP_SEE_OTHER);
    }
}
