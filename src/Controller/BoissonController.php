<?php

namespace App\Controller;

use App\Entity\Boisson;
use App\Form\BoissonType;
use App\Form\FilterOrSearch\FilterMenuType;
use App\Repository\BoissonRepository;
use Doctrine\ORM\EntityManagerInterface;
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
     * @Route("/", name="boisson_index", methods={"GET","POST"})
     */
    public function index(BoissonRepository $boissonRepo,
                          Request           $request, PaginatorInterface $paginator): Response
    {
        $boissons = $boissonRepo->findAll();

        $form = $this->createForm(FilterMenuType::class);
        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boissons = $boissonRepo->filter(
                $filter->get('dispo')->getData(),
                $filter->get('ordre')->getData()
            );
        }

        $boissons = $paginator->paginate(
            $boissons,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('boisson/index.html.twig', [
            'boissons' => $boissons,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="boisson_new", methods={"GET", "POST"})
     */
    public function new(Request          $request, EntityManagerInterface $entityManager,
                        SluggerInterface $slugger): Response
    {
        $boisson = new Boisson();
        $form = $this->createForm(BoissonType::class, $boisson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $fichierBoisson */
            $fichierBoisson = $form->get('imageBoisson')->getData();

            if ($fichierBoisson) {
                $originalFilename = pathinfo($fichierBoisson
                    ->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '.' . $fichierBoisson->guessExtension();

                // Move the file to the directory where brochures are stored
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

            $this->addFlash(
                'SuccessBoisson',
                'La boisson a été sauvegardée !'
            );

            $entityManager->persist($boisson);
            $entityManager->flush();

            return $this->redirectToRoute('boisson_new');
        }

        return $this->renderForm('boisson/new.html.twig', [
            'boisson' => $boisson,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="boisson_edit", methods={"GET", "POST"})
     */
    public function edit(Request          $request, Boisson $boisson, EntityManagerInterface $entityManager,
                         SluggerInterface $slugger): Response
    {
        $oldImgBoisson = $boisson->getImageBoisson();
        $form = $this->createForm(BoissonType::class, $boisson, ['fichierRequired' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichierBoisson */
            $fichierBoisson = $form->get('imageBoisson')->getData();

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
                unlink($this->getParameter('boisson_directory') . '/' . $oldImgBoisson);
                $boisson->setImageBoisson($newFilename);

            }

            $this->addFlash(
                'SuccessBoisson',
                'La boisson a été modifiée !'
            );
            $entityManager->flush();

            return $this->redirectToRoute('boisson_edit', ['id' => $boisson->getId()]);
        }

        return $this->renderForm('boisson/edit.html.twig', [
            'boisson' => $boisson,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/delete_view", name="boisson_delete_view", methods={"GET"})
     */
    public function delete_view(Boisson $boisson): Response
    {
        return $this->render('boisson/delete_view.html.twig', [
            'boisson' => $boisson,
        ]);
    }

    /**
     * @Route("/{id}", name="boisson_delete", methods={"POST"})
     */
    public function delete(Request $request, Boisson $boisson, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $boisson->getId(), $request->request->get('_token'))) {
            unlink($this->getParameter('boisson_directory') . '/' . $boisson->getImageBoisson());
            $entityManager->remove($boisson);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteBoisson',
                'La boisson a été supprimée !'
            );
        }

        return $this->redirectToRoute('boisson_index', [], Response::HTTP_SEE_OTHER);
    }
}
