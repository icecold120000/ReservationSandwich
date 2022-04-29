<?php

namespace App\Controller;

use App\Entity\MenuAccueil;
use App\Form\MenuType;
use App\Repository\MenuAccueilRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class HomepageController extends AbstractController
{
    /**
     * @Route("/", name="homepage", methods={"GET","POST"})
     * @throws NonUniqueResultException
     */
    public function index(Request               $request,
                          SluggerInterface      $slugger,
                          ManagerRegistry       $doctrine,
                          MenuAccueilRepository $menuRepo): Response
    {
        $entityManager = $doctrine->getManager();
        $menuAccueil = new MenuAccueil();
        $form = $this->createForm(MenuType::class);
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $fichierEleve */
            $fichierEleve = $form->get('fileSubmit')->getData();

            if ($fichierEleve) {
                $originalFilename = pathinfo($fichierEleve->getClientOriginalName(), PATHINFO_FILENAME);
                // garantie que le nom du fichier soit dans l'URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '.' . $fichierEleve->guessExtension();

                // Déplace le fichier dans le directory où il sera stocké
                try {
                    $fichierEleve->move(
                        $this->getParameter('menu_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu. Veuillez retransférer votre liste !");
                }
                $menuAccueil->setFileName($newFilename);
            }

            $entityManager->persist($menuAccueil);
            $entityManager->flush();

            // Affiche le message de validation
            $this->addFlash(
                'SuccessMenu',
                'Le menu a été modifié !'
            );

            return $this->redirectToRoute('homepage');
        }

        return $this->render('homepage/index.html.twig', [
            'form' => $form->createView(),
            'menu' => $menuRepo->findCurrentOne(count($menuRepo->findAll())),
        ]);
    }


}
