<?php

namespace App\Controller;

use App\Entity\Adulte;
use App\Form\AdulteType;
use App\Form\FilterOrSearch\FilterAdulteType;
use App\Repository\AdulteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
     * @Route("/", name="adulte_index", methods={"GET","POST"})
     */
    public function index(AdulteRepository $adulteRepo,
                          Request $request, PaginatorInterface $paginator): Response
    {
        $adultes = $adulteRepo->findByArchive(false);

        $form = $this->createForm(FilterAdulteType::class);

        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adultes = $adulteRepo->filter(
                $filter->get('ordreNom')->getData(),
                $filter->get('ordrePrenom')->getData(),
                $filter->get('archiveAdulte')->getData()
            );
        }

        $adultes = $paginator->paginate(
            $adultes,
            $request->query->getInt('page',1),
            20
        );

        return $this->render('adulte/index.html.twig', [
            'adultes' => $adultes,
            'form' => $form->createView(),
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
            $this->addFlash(
                'SuccessAdulte',
                'L\'adulte a été sauvegardé !'
            );
            return $this->redirectToRoute('adulte_new', [], Response::HTTP_SEE_OTHER);
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
            $this->addFlash(
                'SuccessAdulte',
                'L\'adulte a été modifié !'
            );
            return $this->redirectToRoute('adulte_edit', ['id' => $adulte->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('adulte/edit.html.twig', [
            'adulte' => $adulte,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/delete_view", name="adulte_delete_view", methods={"GET"})
     */
    public function delete_view(Adulte $adulte): Response
    {
        return $this->render('adulte/delete_view.html.twig', [
            'adulte' => $adulte,
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
