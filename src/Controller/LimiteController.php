<?php

namespace App\Controller;

use App\Entity\LimitationCommande;
use App\Form\FilterOrSearch\FilterLimitationType;
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
     * Gestion des limitations
     * @Route("/index", name="limite_index", methods={"GET","POST"})
     * @param Request $request
     * @param LimitationCommandeRepository $limitationCommandeRepository
     * @return Response
     */
    public function index(Request                      $request,
                          LimitationCommandeRepository $limitationCommandeRepository): Response
    {
        /*Récupération des limitations*/
        $limites = $limitationCommandeRepository->findAll();
        $form = $this->createForm(FilterLimitationType::class);
        $filter = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $limites = $limitationCommandeRepository->filter(
                $filter->get('ordreLibelle')->getData(),
                $filter->get('limiteActive')->getData(),
                $filter->get('ordreNombre')->getData(),
                $filter->get('ordreHeure')->getData()
            );
        }

        return $this->render('limite/index.html.twig', [
            'limitations' => $limites,
            'form' => $filter->createView()
        ]);
    }

    /**
     * Formulaire d'ajout d'une limitation
     * À Garder pour les développeurs
     * @Route("/new", name="limite_new", methods={"GET", "POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $limitationCommande = new LimitationCommande();
        $form = $this->createForm(LimitationCommandeType::class, $limitationCommande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Permet de mettre null dans le champ nbLimite si l'utilisateur
             a saisi une limite d'heure et inversement*/
            $libelle = $form->get('libelleLimite')->getData();
            if (str_contains($libelle, 'Heure')) {
                $limitationCommande->setNbLimite(null);
            } else {
                $limitationCommande->setHeureLimite(null);
            }

            $entityManager->persist($limitationCommande);
            $entityManager->flush();

            /*Message de validation*/
            $this->addFlash(
                'SuccessLimite',
                'Votre limitation a été sauvegardée !'
            );
            return $this->redirectToRoute('limite_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('limite/new.html.twig', [
            'limitation_commande' => $limitationCommande,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de pré-suppression d'une limitation
     * À Garder pour les développeurs
     * @Route("/{id}/delete_view", name="limite_delete_view", methods={"GET"})
     * @param LimitationCommande $limitationCommande
     * @return Response
     */
    public function delete_view(LimitationCommande $limitationCommande): Response
    {
        return $this->render('limite/delete_view.html.twig', [
            'limitation' => $limitationCommande,
        ]);
    }

    /**
     * Formulaire de modification d'une limitation
     * @Route("/{id}/edit", name="limite_edit", methods={"GET", "POST"})
     * @param Request $request
     * @param LimitationCommande $limitationCommande
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Request                $request,
                         LimitationCommande     $limitationCommande,
                         EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LimitationCommandeType::class, $limitationCommande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Permet de mettre null dans le champ nbLimite si l'utilisateur
            a saisi une limite d'heure et inversement*/
            $libelle = $form->get('libelleLimite')->getData();
            if (str_contains($libelle, 'Heure')) {
                $limitationCommande->setNbLimite(null);
            } else {
                $limitationCommande->setHeureLimite(null);
            }

            $entityManager->flush();
            $this->addFlash(
                'SuccessLimite',
                'Votre limitation a été modifiée !'
            );

            return $this->redirectToRoute('limite_edit', ['id' => $limitationCommande->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('limite/edit.html.twig', [
            'limitation_commande' => $limitationCommande,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de suppression d'une limitation
     * À Garder pour les développeurs
     * @Route("/{id}", name="limite_delete", methods={"POST"})
     * @param Request $request
     * @param LimitationCommande $limitationCommande
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(Request                $request,
                           LimitationCommande     $limitationCommande,
                           EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $limitationCommande->getId(), $request->request->get('_token'))) {
            $entityManager->remove($limitationCommande);
            $entityManager->flush();

            /*Message de validation*/
            $this->addFlash(
                'SuccessDeleteLimite',
                'La limitation a été supprimée !'
            );
        }

        return $this->redirectToRoute('limite_index', [], Response::HTTP_SEE_OTHER);
    }
}
