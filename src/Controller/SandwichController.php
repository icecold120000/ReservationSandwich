<?php

namespace App\Controller;

use App\Entity\Sandwich;
use App\Form\FilterOrSearch\FilterMenuType;
use App\Form\SandwichType;
use App\Repository\SandwichRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
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
     * Gestion des sandwichs
     * @Route("/index/{page}",defaults={"page": 1}, name="sandwich_index", methods={"GET","POST"})
     * @param SandwichRepository $sandwichRepo
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param int $page
     * @return Response
     */
    public function index(SandwichRepository $sandwichRepo,
                          Request            $request,
                          PaginatorInterface $paginator,
                          int                $page = 1): Response
    {
        /*Récupération des sandwichs*/
        $sandwiches = $sandwichRepo->findAll();
        $form = $this->createForm(FilterMenuType::class, null, ['method' => 'GET']);
        $filter = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $sandwiches = $sandwichRepo->filter(
                $filter->get('dispo')->getData(),
                $filter->get('ordre')->getData()
            );
        }

        /*Pagination*/
        $sandwiches = $paginator->paginate(
            $sandwiches,
            $page,
            10
        );

        return $this->render('sandwich/index.html.twig', [
            'sandwiches' => $sandwiches,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire d'ajout d'un sandwich
     * @Route("/new", name="sandwich_new", methods={"GET", "POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SluggerInterface $slugger
     * @param SandwichRepository $sandwichRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function new(Request                $request,
                        EntityManagerInterface $entityManager,
                        SluggerInterface       $slugger,
                        SandwichRepository     $sandwichRepo): Response
    {
        $sandwich = new Sandwich();
        $form = $this->createForm(SandwichType::class, $sandwich);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Vérifie si le sandwich saisi existe déjà*/
            $sandwichFound = $sandwichRepo->findOneByNom($form->get('nomSandwich')->getData());

            /*Si le sandwich saisi n'existe pas alors*/
            if (!$sandwichFound) {
                /*Le sandwich est créé*/
                /** @var UploadedFile $fichierSandwich */
                $fichierSandwich = $form->get('imageSandwich')->getData();
                if ($fichierSandwich) {
                    $originalFilename = pathinfo($fichierSandwich
                        ->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '.' . $fichierSandwich->guessExtension();

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

                /*Message de validation*/
                $this->addFlash(
                    'SuccessSandwich',
                    'Le sandwich a été sauvegardé !'
                );
            } else {
                /*Sinon un message d'erreur s'affiche*/
                $this->addFlash(
                    'ErreurNomSandwich',
                    'Le sandwich saisie existe déjà !'
                );
            }

            return $this->redirectToRoute('sandwich_new');
        }

        return $this->render('sandwich/new.html.twig', [
            'sandwich' => $sandwich,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de modification d'un sandwich
     * @Route("/{id}/edit", name="sandwich_edit", methods={"GET", "POST"})
     * @param Request $request
     * @param Sandwich $sandwich
     * @param EntityManagerInterface $entityManager
     * @param SluggerInterface $slugger
     * @param SandwichRepository $sandwichRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function edit(Request                $request,
                         Sandwich               $sandwich,
                         EntityManagerInterface $entityManager,
                         SluggerInterface       $slugger,
                         SandwichRepository     $sandwichRepo): Response
    {
        /*Récupèration de l'ancienne image*/
        $oldSandwich = $sandwich->getImageSandwich();
        $form = $this->createForm(SandwichType::class, $sandwich, ['fichierRequired' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Vérifie si le sandwich saisi existe déjà*/
            $sandwichFound = $sandwichRepo->findOneByNom($form->get('nomSandwich')->getData());

            /*Si le sandwich saisi est trouvé avec ce nom et que nom a changé alors*/
            if ($sandwichFound && $sandwich->getNomSandwich() != $form->get('nomSandwich')->getData()) {
                /*Un message d'erreur s'affiche*/
                $this->addFlash(
                    'ErreurNomSandwich',
                    'Le sandwich saisie existe déjà !'
                );
            } else {
                /*Le sandwich est modifié*/
                /** @var UploadedFile $fichierSandwich */
                $fichierSandwich = $form->get('imageSandwich')->getData();
                /*Vérifie si l'image a changé*/
                if ($fichierSandwich) {
                    $originalFilename = pathinfo($fichierSandwich
                        ->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '.' . $fichierSandwich->guessExtension();

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
                    unlink($this->getParameter('sandwich_directory') . $oldSandwich);
                    $sandwich->setImageSandwich($newFilename);
                }

                $entityManager->flush();

                /*Message de validation*/
                $this->addFlash(
                    'SuccessSandwich',
                    'Le sandwich a été modifié !'
                );
            }

            return $this->redirectToRoute('sandwich_edit', ['id' => $sandwich->getId()]);
        }

        return $this->render('sandwich/edit.html.twig', [
            'sandwich' => $sandwich,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de pré-suppression d'un sandwich
     * @Route("/{id}/delete_view", name="sandwich_delete_view", methods={"GET"})
     * @param Sandwich $sandwich
     * @return Response
     */
    public function delete_view(Sandwich $sandwich): Response
    {
        return $this->render('sandwich/delete_view.html.twig', [
            'sandwich' => $sandwich,
        ]);
    }

    /**
     * Formulaire de supression d'un sandwich
     * @Route("/{id}", name="sandwich_delete", methods={"POST"})
     * @param Request $request
     * @param Sandwich $sandwich
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(Request                $request,
                           Sandwich               $sandwich,
                           EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $sandwich->getId(), $request->request->get('_token'))) {
            /*Supprime l'image de sandwich*/
            unlink($this->getParameter('sandwich_directory') . $sandwich->getImageSandwich());
            $entityManager->remove($sandwich);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteSandwich',
                'Le sandwich a été supprimé !'
            );
        }

        return $this->redirectToRoute('sandwich_index');
    }
}
