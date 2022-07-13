<?php

namespace App\Controller;

use App\Entity\Boisson;
use App\Form\BoissonType;
use App\Form\FilterOrSearch\FilterMenuType;
use App\Repository\BoissonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Bundle\PaginatorBundle\KnpPaginatorBundle;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/boisson")
 */
class BoissonController extends AbstractController
{
    /**
     * Page de gestion des boissons
     * @Route("/index/{page}",defaults={"page" : 1}, name="boisson_index", methods={"GET","POST"})
     * @param BoissonRepository $boissonRepo
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param int $page Utilisé pour les filtres et la pagination
     * @return Response
     */
    public function index(BoissonRepository  $boissonRepo,
                          Request            $request,
                          PaginatorInterface $paginator,
                          int                $page = 1): Response
    {
        /*Récupération des boissons*/
        $boissons = $boissonRepo->findAll();
        $form = $this->createForm(FilterMenuType::class, null, ['method' => 'GET']);
        $filter = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $boissons = $boissonRepo->filter(
                $filter->get('dispo')->getData(),
                $filter->get('ordre')->getData()
            );
        }

        /*Pagination*/
        $boissons = $paginator->paginate(
            $boissons,
            $page,
            10
        );

        return $this->render('boisson/index.html.twig', [
            'boissons' => $boissons,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire d'ajout d'une boisson
     * @Route("/new", name="boisson_new", methods={"GET", "POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SluggerInterface $slugger
     * @param BoissonRepository $boissonRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function new(Request                $request,
                        EntityManagerInterface $entityManager,
                        SluggerInterface       $slugger,
                        BoissonRepository      $boissonRepo): Response
    {
        $boisson = new Boisson();
        $form = $this->createForm(BoissonType::class, $boisson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Vérifie si la boisson existe déjà*/
            $boissonFound = $boissonRepo->findOneByNom($form->get('nomBoisson')->getData());

            /*Si la boisson n'existe pas alors*/
            if (!$boissonFound) {
                /*Elle est créée*/
                /** @var UploadedFile $fichierBoisson */
                $fichierBoisson = $form->get('imageBoisson')->getData();
                /*Vérifie si le champ image boisson est rempli*/
                if ($fichierBoisson) {
                    $originalFilename = pathinfo($fichierBoisson
                        ->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '.' . $fichierBoisson->guessExtension();

                    // Move the file to the directory where drinks are stored
                    try {
                        $fichierBoisson->move(
                            $this->getParameter('boisson_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        throw new FileException("Fichier corrompu.
                     Veuillez retransférer votre fichier !");
                    }

                    $boisson->setImageBoisson($newFilename);
                }

                $entityManager->persist($boisson);
                $entityManager->flush();

                /*Message de validation*/
                $this->addFlash(
                    'SuccessBoisson',
                    'La boisson a été sauvegardée !'
                );
            } else {
                /*Sinon un message d'erreur*/
                $this->addFlash(
                    'ErreurNomBoisson',
                    'La boisson saisie existe déjà !'
                );
            }


            return $this->redirectToRoute('boisson_new');
        }

        return $this->render('boisson/new.html.twig', [
            'boisson' => $boisson,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de suppression d'une boisson
     * @Route("/{id}/edit", name="boisson_edit", methods={"GET", "POST"})
     * @param Request $request
     * @param Boisson $boisson
     * @param EntityManagerInterface $entityManager
     * @param SluggerInterface $slugger
     * @param BoissonRepository $boissonRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function edit(Request                $request,
                         Boisson                $boisson,
                         EntityManagerInterface $entityManager,
                         SluggerInterface       $slugger,
                         BoissonRepository      $boissonRepo): Response
    {
        /*Récupération de l'ancienne image*/
        $oldImgBoisson = $boisson->getImageBoisson();
        $form = $this->createForm(BoissonType::class, $boisson, ['fichierRequired' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Vérifie si la boisson saisie existe déjà*/
            $boissonFound = $boissonRepo->findOneByNom($form->get('nomBoisson')->getData());

            /*Si la boisson saisie est trouvée avec ce nom et que nom a changé alors*/
            if ($boissonFound && $boisson->getNomBoisson() != $form->get('nomBoisson')->getData()) {
                /*Un message d'erreur s'affiche*/
                $this->addFlash(
                    'ErreurNomBoisson',
                    'La boisson saisie existe déjà !'
                );
            } else {
                /*Sinon la boisson est créée*/
                /** @var UploadedFile $fichierBoisson */
                $fichierBoisson = $form->get('imageBoisson')->getData();
                /*Vérifie si l'image a changé*/
                if ($fichierBoisson) {
                    $originalFilename = pathinfo($fichierBoisson
                        ->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '.' . $fichierBoisson->guessExtension();

                    // Move the file to the directory where boissons are stored
                    try {
                        $fichierBoisson->move(
                            $this->getParameter('boisson_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        throw new FileException("Fichier corrompu.
                     Veuillez retransférer votre fichier !");
                    }

                    /*Vérifie si la nouvelle image n'a pas le même nom que l'ancienne*/
                    if ($newFilename != $oldImgBoisson) {
                        /*Vérifie si l'ancienne image est dans l'emplacement où il est enregistré*/
                        if (file_exists($this->getParameter('boisson_directory') . $oldImgBoisson)) {
                            /*Supprime le fichier*/
                            unlink($this->getParameter('boisson_directory') . $oldImgBoisson);
                        }
                    }
                    $boisson->setImageBoisson($newFilename);
                }

                $entityManager->flush();

                /*Message de validation*/
                $this->addFlash(
                    'SuccessBoisson',
                    'La boisson a été modifiée !'
                );
            }

            return $this->redirectToRoute('boisson_edit', ['id' => $boisson->getId()]);
        }

        return $this->render('boisson/edit.html.twig', [
            'boisson' => $boisson,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de pré-suppression d'une boisson
     * @Route("/{id}/delete_view", name="boisson_delete_view", methods={"GET"})
     * @param Boisson $boisson
     * @return Response
     */
    public function delete_view(Boisson $boisson): Response
    {
        return $this->render('boisson/delete_view.html.twig', [
            'boisson' => $boisson,
        ]);
    }

    /**
     * Formulaire de suppression d'une boisson
     * @Route("/{id}", name="boisson_delete", methods={"POST"})
     * @param Request $request
     * @param Boisson $boisson
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(Request                $request,
                           Boisson                $boisson,
                           EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $boisson->getId(), $request->request->get('_token'))) {

            /*Vérifie si l'image est à son emplacement où elle est enregistrée*/
            if (file_exists($this->getParameter('boisson_directory') . $boisson->getImageBoisson())) {
                /*Supprime le fichier*/
                unlink($this->getParameter('boisson_directory') . $boisson->getImageBoisson());
            }
            $entityManager->remove($boisson);
            $entityManager->flush();

            /*Message de validation*/
            $this->addFlash(
                'SuccessDeleteBoisson',
                'La boisson a été supprimée !'
            );
        }

        return $this->redirectToRoute('boisson_index', [], Response::HTTP_SEE_OTHER);
    }
}
