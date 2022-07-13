<?php

namespace App\Controller;

use App\Entity\LieuLivraison;
use App\Form\FilterOrSearch\FilterLieuType;
use App\Form\LieuLivraisonType;
use App\Repository\CommandeGroupeRepository;
use App\Repository\LieuLivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
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
     * Page de gestion des lieux de livraison
     * @Route("/index/{page}",defaults={"page":1}, name="lieu_livraison_index", methods={"GET","POST"})
     * @param LieuLivraisonRepository $lieuLivraisonRepo
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @param int $page Utilisé dans les filtres et la pagination
     * @return Response
     */
    public function index(LieuLivraisonRepository $lieuLivraisonRepo,
                          PaginatorInterface      $paginator,
                          Request                 $request,
                          int                     $page = 1): Response
    {
        /*Récupération des lieux de livraison*/
        $lieux = $lieuLivraisonRepo->findAll();
        $form = $this->createForm(FilterLieuType::class, null, ['method' => 'GET']);
        $search = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $lieux = $lieuLivraisonRepo->filter(
                $search->get('lieuActive')->getData(),
                $search->get('ordreLieu')->getData()
            );
        }

        /*Pagination*/
        $lieux = $paginator->paginate(
            $lieux,
            $page,
            10
        );

        return $this->render('lieu_livraison/index.html.twig', [
            'lieu_livraisons' => $lieux,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire d'ajout d'un lieu de livraison
     * @Route("/new", name="lieu_livraison_new", methods={"GET","POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param LieuLivraisonRepository $lieuLivraisonRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function new(Request                 $request,
                        EntityManagerInterface  $entityManager,
                        LieuLivraisonRepository $lieuLivraisonRepo): Response
    {
        $lieuLivraison = new LieuLivraison();
        $form = $this->createForm(LieuLivraisonType::class, $lieuLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /*Vérifie si le lieu saisi existe déjà*/
            $lieuFound = $lieuLivraisonRepo->findOneByLibelle($form->get('libelleLieu')->getData());

            /*Si le lieu n'existe pas alors*/
            if (!$lieuFound) {
                /*Le lieu est créé*/
                $entityManager->persist($lieuLivraison);
                $entityManager->flush();

                /*Message de validation*/
                $this->addFlash(
                    'SuccessLieu',
                    'Le lieu de livraison a été sauvegardé !'
                );
            } else {
                /*Sinon un message d'erreur s'affiche*/
                $this->addFlash(
                    'ErreurLibelleLieu',
                    'Le lieu saisie existe déjà !'
                );
            }

            return $this->redirectToRoute('lieu_livraison_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lieu_livraison/new.html.twig', [
            'lieu_livraison' => $lieuLivraison,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de pré-suppression d'un lieu de livraison
     * @Route("/{id}/delete_view", name="lieu_livraison_delete_view", methods={"GET","POST"})
     * @param LieuLivraison $lieuLivraison
     * @return Response
     */
    public function delete_view(LieuLivraison $lieuLivraison): Response
    {
        return $this->render('lieu_livraison/delete_view.html.twig', [
            'lieu_livraison' => $lieuLivraison,
        ]);
    }

    /**
     * Formulaire de modification d'un lieu de livraison
     * @Route("/{id}/edit", name="lieu_livraison_edit", methods={"GET","POST"})
     * @param Request $request
     * @param LieuLivraison $lieuLivraison
     * @param EntityManagerInterface $entityManager
     * @param LieuLivraisonRepository $lieuLivraisonRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function edit(Request                 $request,
                         LieuLivraison           $lieuLivraison,
                         EntityManagerInterface  $entityManager,
                         LieuLivraisonRepository $lieuLivraisonRepo): Response
    {
        $form = $this->createForm(LieuLivraisonType::class, $lieuLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Vérifie si le lieu saisi existe déjà*/
            $lieuFound = $lieuLivraisonRepo->findOneByLibelle($form->get('libelleLieu')->getData());

            /*Si le lieu saisi est trouvé avec ce nom et que nom a changé alors*/
            if ($lieuFound && $lieuLivraison->getLibelleLieu() != $form->get('libelleLieu')->getData()) {
                $this->addFlash(
                    'ErreurLibelleLieu',
                    'Le lieu de livraison a été modifiée !'
                );
            } else {
                $entityManager->flush();

                /*Message de validation*/
                $this->addFlash(
                    'SuccessLieu',
                    'Le lieu de livraison a été modifiée !'
                );
            }

            return $this->redirectToRoute('lieu_livraison_edit', ['id' => $lieuLivraison->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lieu_livraison/edit.html.twig', [
            'lieu_livraison' => $lieuLivraison,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de suppression d'un lieu de livraison
     * @Route("/{id}", name="lieu_livraison_delete", methods={"POST"})
     * @param Request $request
     * @param LieuLivraison $lieuLivraison
     * @param EntityManagerInterface $entityManager
     * @param CommandeGroupeRepository $comGroupeRepo
     * @param LieuLivraisonRepository $lieuRepository
     * @return Response
     */
    public function delete(Request                  $request,
                           LieuLivraison            $lieuLivraison,
                           EntityManagerInterface   $entityManager,
                           CommandeGroupeRepository $comGroupeRepo,
                           LieuLivraisonRepository  $lieuRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $lieuLivraison->getId(), $request->request->get('_token'))) {
            /*Modifie toutes les commandes groupées qui utilisent le lieu de livraison
              et les affectent au lieu de livraison aucun
             */
            $commandesGroupe = $comGroupeRepo->findByLieuLivraison($lieuLivraison->getId());
            foreach ($commandesGroupe as $commandeGroupe) {
                $commandeGroupe->setLieuLivraison($lieuRepository->find(['id' => 1]));
            }
            $entityManager->remove($lieuLivraison);
            $entityManager->flush();

            /*Message de validation*/
            $this->addFlash(
                'SuccessDeleteLieu',
                'Le lieu a été supprimé !'
            );
        }

        return $this->redirectToRoute('lieu_livraison_index', [], Response::HTTP_SEE_OTHER);
    }
}
