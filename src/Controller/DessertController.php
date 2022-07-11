<?php

namespace App\Controller;

use App\Entity\Dessert;
use App\Form\DessertType;
use App\Form\FilterOrSearch\FilterMenuType;
use App\Repository\DessertRepository;
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
 * @Route("/dessert")
 */
class DessertController extends AbstractController
{
    /**
     * Page de gestion des desserts
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
        /*Récupération des desserts*/
        $desserts = $dessertRepo->findAll();
        $form = $this->createForm(FilterMenuType::class, null, ['method' => 'GET']);
        $filter = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $desserts = $dessertRepo->filter(
                $filter->get('dispo')->getData(),
                $filter->get('ordre')->getData()
            );
        }

        /*Pagination*/
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
     * Formulaire d'ajout d'un dessert
     * @Route("/new", name="dessert_new", methods={"GET", "POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SluggerInterface $slugger
     * @param DessertRepository $dessertRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function new(Request                $request,
                        EntityManagerInterface $entityManager,
                        SluggerInterface       $slugger,
                        DessertRepository      $dessertRepo): Response
    {
        $dessert = new Dessert();
        $form = $this->createForm(DessertType::class, $dessert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Vérifie si le dessert saisi existe déjà*/
            $dessertFound = $dessertRepo->findOneByNom($form->get('nomDessert')->getData());

            /*Si le dessert saisi n'existe pas alors*/
            if (!$dessertFound) {
                /*Le dessert est créé*/
                /** @var UploadedFile $fichierDessert */
                $fichierDessert = $form->get('imageDessert')->getData();
                /*Vérifie si l'image du dessert est rempli*/
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

                $entityManager->persist($dessert);
                $entityManager->flush();

                /*Message de validation*/
                $this->addFlash(
                    'SuccessDessert',
                    'Le dessert a été sauvegardé !'
                );
            } else {
                /*Sinon un message d'erreur s'affiche*/
                $this->addFlash(
                    'ErreurNomDessert',
                    'Le dessert saisi existe déjà !'
                );
            }

            return $this->redirectToRoute('dessert_new');
        }

        return $this->render('dessert/new.html.twig', [
            'dessert' => $dessert,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de modification d'un dessert
     * @Route("/{id}/edit", name="dessert_edit", methods={"GET", "POST"})
     * @param Request $request
     * @param Dessert $dessert
     * @param EntityManagerInterface $entityManager
     * @param SluggerInterface $slugger
     * @param DessertRepository $dessertRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function edit(Request                $request,
                         Dessert                $dessert,
                         EntityManagerInterface $entityManager,
                         SluggerInterface       $slugger,
                         DessertRepository      $dessertRepo): Response
    {
        /*Récupération de l'ancienne image*/
        $oldDessert = $dessert->getImageDessert();
        $form = $this->createForm(DessertType::class, $dessert, ['fichierRequired' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Vérifie si le dessert saisi existe déjà*/
            $dessertFound = $dessertRepo->findOneByNom($form->get('nomDessert')->getData());

            /*Si le dessert saisi est trouvé avec ce nom et que nom a changé alors*/
            if ($dessertFound && $dessert->getNomDessert() != $form->get('nomDessert')->getData()) {
                $this->addFlash(
                    'ErreurNomDessert',
                    'Le dessert saisi existe déjà !'
                );
            } else {
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

                    /*Vérifie si la nouvelle image de dessert est différente de l'ancienne*/
                    if ($newFilename != $oldDessert) {
                        /*Vérifie si l'image est à son emplacement où elle est enregistrée*/
                        if (file_exists($this->getParameter('dessert_directory') . $oldDessert)) {
                            /*Supprime le fichier*/
                            unlink($this->getParameter('dessert_directory') . $oldDessert);
                        }
                    }

                    $dessert->setImageDessert($newFilename);
                }

                $entityManager->flush();

                /*Message de validation*/
                $this->addFlash(
                    'SuccessDessert',
                    'Le dessert a été modifié !'
                );
            }

            return $this->redirectToRoute('dessert_edit', ['id' => $dessert->getId()]);
        }

        return $this->render('dessert/edit.html.twig', [
            'dessert' => $dessert,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de pré-suppression d'un dessert
     * @Route("/{id}/delete_view", name="dessert_delete_view", methods={"GET"})
     * @param Dessert $dessert
     * @return Response
     */
    public function delete_view(Dessert $dessert): Response
    {
        return $this->render('dessert/delete_view.html.twig', [
            'dessert' => $dessert,
        ]);
    }

    /**
     * Formulaire de suppression d'un dessert
     * @Route("/{id}", name="dessert_delete", methods={"POST"})
     * @param Request $request
     * @param Dessert $dessert
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(Request                $request,
                           Dessert                $dessert,
                           EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $dessert->getId(), $request->request->get('_token'))) {
            /*Vérifie si l'image est à son emplacement où elle est enregistrée*/
            if (file_exists($this->getParameter('dessert_directory') . $dessert->getImageDessert())) {
                /*Supprime le fichier*/
                unlink($this->getParameter('dessert_directory') . $dessert->getImageDessert());
            }
            $entityManager->remove($dessert);
            $entityManager->flush();

            /*Message de validation*/
            $this->addFlash(
                'SuccessDeleteDessert',
                'Le dessert a été supprimé !'
            );
        }

        return $this->redirectToRoute('dessert_index');
    }
}
