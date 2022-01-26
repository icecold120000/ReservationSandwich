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
     * @Route("/", name="dessert_index", methods={"GET","POST"})
     */
    public function index(DessertRepository $dessertRepo,Request $request,
                          PaginatorInterface $paginator): Response
    {
        $desserts = $dessertRepo->findAll();

        $form = $this->createForm(FilterMenuType::class);
        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $desserts = $dessertRepo->filter(
                $filter->get('ordre')->getData(),
                $filter->get('dispo')->getData()
            );
        }

        $desserts = $paginator->paginate(
            $desserts,
            $request->query->getInt('page',1),
            10
        );

        return $this->render('dessert/index.html.twig', [
            'desserts' => $desserts,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="dessert_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager,
                        SluggerInterface $slugger): Response
    {
        $dessert = new Dessert();
        $form = $this->createForm(DessertType::class, $dessert,
            array('row_attr' => array('route' => $request->get('_route'))));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $fichierDessert */
            $fichierDessert = $form->get('imageDessert')->getData();
            
            if ($fichierDessert) {
                $originalFilename = pathinfo($fichierDessert
                    ->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'.'.$fichierDessert->guessExtension();

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
     */
    public function edit(Request $request, Dessert $dessert, EntityManagerInterface $entityManager,
                        SluggerInterface $slugger): Response
    {
        $oldDessert = $dessert->getImageDessert();
        $form = $this->createForm(DessertType::class, $dessert,
            array('row_attr' => array('route' => $request->get('_route'))));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichierDessert */
            $fichierDessert = $form->get('imageDessert')->getData();

            if ($fichierDessert) {
                $originalFilename = pathinfo($fichierDessert
                    ->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'.'.$fichierDessert->guessExtension();

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
                unlink($this->getParameter('dessert_directory').'/'.$oldDessert);
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
     */
    public function delete_view(Dessert $dessert): Response
    {
        return $this->render('dessert/delete_view.html.twig', [
            'dessert' => $dessert,
        ]);
    }

    /**
     * @Route("/{id}", name="dessert_delete", methods={"POST"})
     */
    public function delete(Request $request, Dessert $dessert, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dessert->getId(), $request->request->get('_token'))) {
            unlink($this->getParameter('dessert_directory').'/'.$dessert->getImageDessert());
            $entityManager->remove($dessert);
            $entityManager->flush();
        }

        return $this->redirectToRoute('dessert_index');
    }
}
