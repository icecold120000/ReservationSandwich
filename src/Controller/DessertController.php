<?php

namespace App\Controller;

use App\Entity\Dessert;
use App\Form\DessertType;
use App\Form\FilterOrSearch\FilterMenuType;
use App\Repository\DessertRepository;
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
 * @Route("/dessert")
 */
class DessertController extends AbstractController
{
    /**
     * @Route("/index/{page}",defaults={"page" : 1}, name="dessert_index", methods={"GET","POST"})
     * @param DessertRepository $dessertRepo
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param int $page Utilisé sur les filtres et la pagination
     * @return Response
     */
    public function index(DessertRepository  $dessertRepo,
                          Request            $request,
                          PaginatorInterface $paginator,
                          int                $page = 1): Response
    {
        $desserts = $dessertRepo->findAll();
        $form = $this->createForm(FilterMenuType::class, null, ['method' => 'GET']);
        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $desserts = $dessertRepo->filter(
                $filter->get('dispo')->getData(),
                $filter->get('ordre')->getData()
            );
        }

        $desserts = $paginator->paginate(
            $desserts,
            $page,
            10
        );

        return $this->render('dessert/index.html.twig', [
            'desserts' => $desserts,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="dessert_new", methods={"GET", "POST"})
     * Formulaire d'ajout d'un dessert
     */
    public function new(Request                $request,
                        EntityManagerInterface $entityManager,
                        SluggerInterface       $slugger): Response
    {
        $dessert = new Dessert();
        $form = $this->createForm(DessertType::class, $dessert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichierDessert */
            $fichierDessert = $form->get('imageDessert')->getData();
            /*Vérifie si l'image du deseert est rempli*/
            if ($fichierDessert) {
                $originalFilename = pathinfo($fichierDessert
                    ->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '.' . $fichierDessert->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $fichierDessert->move(
                        $this->getParameter('dessert_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu.
                     Veuillez retransférer votre fichier !");
                }

                $dessert->setImageDessert($newFilename);
            }

            $this->addFlash(
                'SuccessDessert',
                'Le dessert a été sauvegardé !'
            );

            $entityManager->persist($dessert);
            $entityManager->flush();

            return $this->redirectToRoute('dessert_new');
        }

        return $this->renderForm('dessert/new.html.twig', [
            'dessert' => $dessert,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="dessert_edit", methods={"GET", "POST"})
     * Formulaire de modification d'un dessert
     */
    public function edit(Request                $request,
                         Dessert                $dessert,
                         EntityManagerInterface $entityManager,
                         SluggerInterface       $slugger): Response
    {
        /*Récupération de l'ancienne image*/
        $oldDessert = $dessert->getImageDessert();
        $form = $this->createForm(DessertType::class, $dessert, ['fichierRequired' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichierDessert */
            $fichierDessert = $form->get('imageDessert')->getData();
            /*Vérifie si l'image a changé*/
            if ($fichierDessert) {
                $originalFilename = pathinfo($fichierDessert
                    ->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '.' . $fichierDessert->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $fichierDessert->move(
                        $this->getParameter('dessert_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu.
                     Veuillez retransférer votre fichier !");
                }
                /*Supprimer le fichier de l'ancienne image */
                unlink($this->getParameter('dessert_directory') . $oldDessert);
                $dessert->setImageDessert($newFilename);
            }

            $this->addFlash(
                'SuccessDessert',
                'Le dessert a été modifié !'
            );

            $entityManager->flush();

            return $this->redirectToRoute('dessert_edit', ['id' => $dessert->getId()]);
        }

        return $this->renderForm('dessert/edit.html.twig', [
            'dessert' => $dessert,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/delete_view", name="dessert_delete_view", methods={"GET"})
     * Page de pré-suppression d'un dessert
     */
    public function delete_view(Dessert $dessert): Response
    {
        return $this->render('dessert/delete_view.html.twig', [
            'dessert' => $dessert,
        ]);
    }

    /**
     * @Route("/{id}", name="dessert_delete", methods={"POST"})
     * Formulaire de suppression d'un dessert
     */
    public function delete(Request                $request,
                           Dessert                $dessert,
                           EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $dessert->getId(), $request->request->get('_token'))) {
            /*Supprimer le fichier*/
            unlink($this->getParameter('dessert_directory') . $dessert->getImageDessert());
            $entityManager->remove($dessert);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteDessert',
                'Le dessert a été supprimé !'
            );
        }

        return $this->redirectToRoute('dessert_index');
    }
}
