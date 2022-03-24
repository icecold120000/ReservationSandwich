<?php

namespace App\Controller;

use App\Entity\Sandwich;
use App\Form\FilterOrSearch\FilterMenuType;
use App\Form\SandwichType;
use App\Repository\SandwichRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/sandwich")
 */
class SandwichController extends AbstractController
{
    /**
     * @Route("/", name="sandwich_index", methods={"GET","POST"})
     */
    public function index(SandwichRepository $sandwichRepo, Request $request,
                          PaginatorInterface $paginator): Response
    {

        $sandwiches = $sandwichRepo->findAll();

        $form = $this->createForm(FilterMenuType::class);
        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sandwiches = $sandwichRepo->filter(
                $filter->get('dispo')->getData(),
                $filter->get('ordre')->getData()
            );
        }

        $sandwiches = $paginator->paginate(
            $sandwiches,
            $request->query->getInt('page',1),
            10
        );

        return $this->render('sandwich/index.html.twig', [
            'sandwiches' => $sandwiches,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="sandwich_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager,
                        SluggerInterface $slugger): Response
    {
        $sandwich = new Sandwich();
        $form = $this->createForm(SandwichType::class, $sandwich);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $fichierSandwich */
            $fichierSandwich = $form->get('imageSandwich')->getData();

            if ($fichierSandwich) {
                $originalFilename = pathinfo($fichierSandwich
                    ->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'.'.$fichierSandwich->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $fichierSandwich->move(
                        $this->getParameter('sandwich_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu.
                     Veuillez retransférer votre fichier !");
                }

                $sandwich->setImageSandwich($newFilename);
            }

            $entityManager->persist($sandwich);
            $entityManager->flush();
            $this->addFlash(
                'SuccessSandwich',
                'Le sandwich a été sauvegardé !'
            );
            return $this->redirectToRoute('sandwich_new');
        }

        return $this->renderForm('sandwich/new.html.twig', [
            'sandwich' => $sandwich,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="sandwich_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Sandwich $sandwich, EntityManagerInterface $entityManager,
                         SluggerInterface $slugger): Response
    {
        $oldSandwich = $sandwich->getImageSandwich();
        $form = $this->createForm(SandwichType::class, $sandwich,['fichierRequired' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichierSandwich */
            $fichierSandwich = $form->get('imageSandwich')->getData();

            if ($fichierSandwich) {
                $originalFilename = pathinfo($fichierSandwich
                    ->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'.'.$fichierSandwich->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $fichierSandwich->move(
                        $this->getParameter('sandwich_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu.
                     Veuillez retransférer votre fichier !");
                }
                unlink($this->getParameter('sandwich_directory').'/'.$oldSandwich);
                $sandwich->setImageSandwich($newFilename);
            }

            $entityManager->flush();
            $this->addFlash(
                'SuccessSandwich',
                'Le sandwich a été modifié !'
            );
            return $this->redirectToRoute('sandwich_edit', ['id' => $sandwich->getId()]);
        }

        return $this->renderForm('sandwich/edit.html.twig', [
            'sandwich' => $sandwich,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/delete_view", name="sandwich_delete_view", methods={"GET"})
     */
    public function delete_view(Sandwich $sandwich): Response
    {
        return $this->render('sandwich/delete_view.html.twig', [
            'sandwich' => $sandwich,
        ]);
    }

    /**
     * @Route("/{id}", name="sandwich_delete", methods={"POST"})
     */
    public function delete(Request $request, Sandwich $sandwich, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sandwich->getId(), $request->request->get('_token'))) {
            unlink($this->getParameter('sandwich_directory').'/'.$sandwich->getImageSandwich());
            $entityManager->remove($sandwich);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sandwich_index');
    }
}
