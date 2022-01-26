<?php

namespace App\Controller;

use App\Entity\Adulte;
use App\Form\AdulteType;
use App\Repository\AdulteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/adulte")
 */
class AdulteController extends AbstractController
{
    /**
     * @Route("/", name="adulte_index", methods={"GET"})
     */
    public function index(AdulteRepository $adulteRepository): Response
    {
        return $this->render('adulte/index.html.twig', [
            'adultes' => $adulteRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="adulte_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $adulte = new Adulte();
        $form = $this->createForm(AdulteType::class, $adulte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($adulte);
            $entityManager->flush();

            return $this->redirectToRoute('adulte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('adulte/new.html.twig', [
            'adulte' => $adulte,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="adulte_show", methods={"GET"})
     */
    public function show(Adulte $adulte): Response
    {
        return $this->render('adulte/show.html.twig', [
            'adulte' => $adulte,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="adulte_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Adulte $adulte, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdulteType::class, $adulte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('adulte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('adulte/edit.html.twig', [
            'adulte' => $adulte,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="adulte_delete", methods={"POST"})
     */
    public function delete(Request $request, Adulte $adulte, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$adulte->getId(), $request->request->get('_token'))) {
            $entityManager->remove($adulte);
            $entityManager->flush();
        }

        return $this->redirectToRoute('adulte_index', [], Response::HTTP_SEE_OTHER);
    }
}
