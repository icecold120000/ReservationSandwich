<?php

namespace App\Controller;

use App\Entity\Eleve;
use App\Entity\Fichier;
use App\Entity\InscriptionCantine;
use App\Form\EleveType;
use App\Form\FichierType;
use App\Form\FilterOrSearch\FilterEleveType;
use App\Repository\ClasseRepository;
use App\Repository\EleveRepository;
use App\Repository\InscriptionCantineRepository;
use App\Repository\UserRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Ang3\Component\Serializer\Encoder\ExcelEncoder;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @property EntityManagerInterface $entityManager
 * @Route("/eleve")
 */
class EleveController extends AbstractController
{
    private EleveRepository $eleveRepository;
    private ClasseRepository $classeRepo;
    private InscriptionCantineRepository $inscritCantRepo;

    public function __construct(EntityManagerInterface       $entityManager,
                                EleveRepository              $eleveRepository,
                                ClasseRepository             $classeRepo,
                                InscriptionCantineRepository $inscritCantRepo)
    {
        $this->entityManager = $entityManager;
        $this->eleveRepository = $eleveRepository;
        $this->classeRepo = $classeRepo;
        $this->inscritCantRepo = $inscritCantRepo;
    }

    /**
     * @Route("/", name="eleve_index", methods={"GET","POST"})
     */
    public function index(EleveRepository    $eleveRepo,
                          Request            $request,
                          PaginatorInterface $paginator): Response
    {
        $eleves = $eleveRepo->findByArchive(false);
        $form = $this->createForm(FilterEleveType::class);
        $search = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eleves = $eleveRepo->findByClasse(
                $search->get('nom')->getData(),
                $search->get('classe')->getData(),
                $search->get('archiveEleve')->getData(),
                $search->get('ordreNom')->getData(),
                $search->get('ordrePrenom')->getData()
            );
        }

        $elevesTotal = $eleves;
        $eleves = $paginator->paginate(
            $eleves,
            $request->query->getInt('page', 1),
            40
        );

        return $this->render('eleve/index.html.twig', [
            'eleves' => $eleves,
            'elevesTotal' => $elevesTotal,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/file", name="eleve_file", methods={"GET","POST"})
     * @throws NonUniqueResultException
     */
    public function fileSubmit(Request                $request,
                               SluggerInterface       $slugger,
                               EntityManagerInterface $entityManager): Response
    {
        $eleveFile = new Fichier();
        $form = $this->createForm(FichierType::class, $eleveFile);
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $fichierEleve */
            $fichierEleve = $form->get('fileSubmit')->getData();

            if ($fichierEleve) {
                $originalFilename = pathinfo($fichierEleve->getClientOriginalName(),
                    PATHINFO_FILENAME);
                // garantie que le nom du fichier soit dans l'URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '.' . $fichierEleve->guessExtension();

                // Déplace le fichier dans le directory où il sera stocké
                try {
                    $fichierEleve->move(
                        $this->getParameter('eleveFile_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu. Veuillez retransférer votre liste !");
                }

                $eleveFile->setFileName($newFilename);
            }
            // Modifie la propriété 'filename' pour stocker le nom du fichier XLSX
            $entityManager->persist($eleveFile);
            $entityManager->flush();

            // Traite les données du fichier Excel et l'envoie dans la base de donnée
            EleveController::creerEleves($eleveFile->getFileName());

            // Affiche le message de validation
            $this->addFlash(
                'SuccessEleveFileSubmit',
                'Les élèves ont été sauvegardés ou modifiés !'
            );

            return $this->redirectToRoute('eleve_file');
        }

        return $this->render('eleve/eleveFile.html.twig', [
            'fichierUser' => $eleveFile,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function creerEleves(string $fileName): void
    {
        $eleveCount = 0;

        /* Tableau des élèves non archivés*/
        $elevesNonArchives = $this->eleveRepository->findByArchive(false);

        /* Parcours le tableau donné par le fichier Excel*/

        while ($eleveCount < sizeof($this->getDataFromFile($fileName))) {
            /*Pour chaque élève*/

            foreach ($this->getDataFromFile($fileName) as $row) {
                /*Parcours les données d'un élève*/

                foreach ($row as $rowData) {

                    /*Vérifie s'il existe une colonne nom et qu'elle n'est pas vide*/
                    if (array_key_exists('Nom', $rowData)
                        && !empty($rowData['Nom'])) {
                        /*Recherche l'élève dans la base de donnée*/
                        $eleveExcel = $this->eleveRepository->findByNomPrenomDateNaissance($rowData['Nom'],
                            $rowData['Prénom'],
                            new DateTime($rowData['Date de naissance'])
                        );

                        /*Vérifie si l'élève dans le fichier est dans le tableau des non archivé*/
                        if (in_array($eleveExcel, $elevesNonArchives)) {
                            /*Enlève dans le tableau des non archivé les élèves
                             qui sont dans le fichier excel*/
                            if (($key = array_search($eleveExcel, $elevesNonArchives)) !== false) {
                                unset($elevesNonArchives[$key]);
                            }
                        }

                        /* Si l'élève est trouvé et doit être modifié*/
                        if ($eleveExcel !== Null) {
                            $birthday = new DateTime($rowData['Date de naissance'],
                                new DateTimeZone('Europe/Paris'));

                            if (array_key_exists('Code classe', $rowData)
                                && !empty($rowData['Code classe'])) {
                                $classe = $this->classeRepo
                                    ->findOneByCode($rowData['Code classe']);
                            } else {
                                $classe = null;
                            }

                            if ($rowData['Nombre de repas Midi'] === null) {
                                $nbRepas = 0;
                            } else {
                                $nbRepas = $rowData['Nombre de repas Midi'];
                                $inscription = $this->inscritCantRepo->findOneByEleve($eleveExcel->getId());
                                if ($inscription !== null) {
                                    $inscription
                                        ->setRepasJ1(!empty($rowData['Repas Midi J1']))
                                        ->setRepasJ2(!empty($rowData['Repas Midi J2']))
                                        ->setRepasJ3(!empty($rowData['Repas Midi J3']))
                                        ->setRepasJ4(!empty($rowData['Repas Midi J4']))
                                        ->setRepasJ5(!empty($rowData['Repas Midi J5']));
                                } else {
                                    $inscription = new InscriptionCantine();
                                    $inscription
                                        ->setEleve($eleveExcel)
                                        ->setRepasJ1(!empty($rowData['Repas Midi J1']))
                                        ->setRepasJ2(!empty($rowData['Repas Midi J2']))
                                        ->setRepasJ3(!empty($rowData['Repas Midi J3']))
                                        ->setRepasJ4(!empty($rowData['Repas Midi J4']))
                                        ->setRepasJ5(!empty($rowData['Repas Midi J5']));
                                }
                                $inscription->setArchiveInscription(false);
                                $this->entityManager->persist($inscription);

                            }

                            $eleveExcel
                                ->setPrenomEleve($rowData['Prénom'])
                                ->setNomEleve($rowData['Nom'])
                                ->setDateNaissance($birthday)
                                ->setArchiveEleve(false)
                                ->setClasseEleve($classe)
                                ->setNbRepas($nbRepas);
                        } /*S'il n'existe pas alors on le crée
                         en tant qu'un nouvel élève*/
                        else {
                            $eleveExcel = new Eleve();
                            $birthday = new DateTime($rowData['Date de naissance'],
                                new DateTimeZone('Europe/Paris'));

                            if (array_key_exists('Code classe', $rowData)
                                && !empty($rowData['Code classe'])) {
                                $classe = $this->classeRepo
                                    ->findOneByCode($rowData['Code classe']);
                            } else {
                                $classe = null;
                            }

                            if ($rowData['Nombre de repas Midi'] === null) {
                                $nbRepas = 0;
                            } else {
                                $nbRepas = $rowData['Nombre de repas Midi'];
                                $inscription = new InscriptionCantine();
                                $inscription->setEleve($eleveExcel)
                                    ->setRepasJ1(!empty($rowData['Repas Midi J1']))
                                    ->setRepasJ2(!empty($rowData['Repas Midi J2']))
                                    ->setRepasJ3(!empty($rowData['Repas Midi J3']))
                                    ->setRepasJ4(!empty($rowData['Repas Midi J4']))
                                    ->setRepasJ5(!empty($rowData['Repas Midi J5']));
                                $inscription->setArchiveInscription(false);
                                $this->entityManager->persist($inscription);
                            }

                            $eleveExcel
                                ->setNomEleve($rowData['Nom'])
                                ->setPrenomEleve($rowData['Prénom'])
                                ->setDateNaissance($birthday)
                                ->setArchiveEleve(false)
                                ->setClasseEleve($classe)
                                ->setNbRepas($nbRepas);
                        }

                        $fileNamePhoto = $rowData['Nom'] . ' ' . $rowData['Prénom'] . '.jpg';
                        $eleveExcel->setPhotoEleve($fileNamePhoto);

                        $this->entityManager->persist($eleveExcel);
                        $eleveCount++;
                    }
                }
            }
        }
        /*Reste que tous les élèves non archivés
        qui ont quitté l'établissement*/
        foreach ($elevesNonArchives as $eleve) {
            $eleve
                ->setArchiveEleve(true)
                ->setClasseEleve($this->classeRepo
                    ->findOneByLibelle("Quitter l'établissement"));
            $inscription = $this->inscritCantRepo->findOneByEleve($eleve->getId());
            $inscription?->setArchiveInscription(true);

        }
        $this->entityManager->flush();
    }

    public function getDataFromFile(string $fileName): array
    {
        $file = $this->getParameter('eleveFile_directory') . '/' . $fileName;

        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

        $normalizers = [new ObjectNormalizer()];

        $encoders = [
            new ExcelEncoder($defaultContext = []),
        ];

        $serializer = new Serializer($normalizers, $encoders);

        /** @var string $fileString */
        $fileString = file_get_contents($file);

        return $serializer->decode($fileString, $fileExtension);

    }

    /**
     * @Route("/new", name="eleve_new", methods={"GET","POST"})
     * @throws Exception
     */
    public function new(Request                $request,
                        SluggerInterface       $slugger,
                        EntityManagerInterface $entityManager): Response
    {
        $eleve = new Eleve();
        $form = $this->createForm(EleveType::class, $eleve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $imgProfileEleve */
            $imgProfileEleve = $form->get('photoEleve')->getData();

            if ($imgProfileEleve) {
                $originalFilename = pathinfo($imgProfileEleve->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFileNameImg = $safeFilename . '.' . $imgProfileEleve->guessExtension();

                // Move the file to the directory where photos are stored
                try {
                    $imgProfileEleve->move(
                        $this->getParameter('photoEleve_directory'),
                        $newFileNameImg
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu. Veuillez retransférer votre fichier !");
                }

                $eleve->setPhotoEleve($newFileNameImg);
            }

            $entityManager->persist($eleve);
            $entityManager->flush();

            $this->addFlash(
                'SuccessEleve',
                'L\'élève a été sauvegardé !'
            );

            return $this->redirectToRoute('eleve_new');
        }

        return $this->render('eleve/new.html.twig', [
            'eleve' => $eleve,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}/edit", name="eleve_edit", methods={"GET","POST"})
     * @throws Exception
     */
    public function edit(Request                $request,
                         Eleve                  $eleve,
                         SluggerInterface       $slugger,
                         EntityManagerInterface $entityManager): Response
    {
        $anciennePhoto = $eleve->getPhotoEleve();
        $form = $this->createForm(EleveType::class, $eleve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $imgProfileEleve */
            $imgProfileEleve = $form->get('photoEleve')->getData();

            if ($imgProfileEleve) {
                $originalFilename = pathinfo($imgProfileEleve->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFileNameImg = $safeFilename . '.' . $imgProfileEleve->guessExtension();

                if ($newFileNameImg != $anciennePhoto && $anciennePhoto != null) {
                    unlink($this->getParameter('photoEleve_directory') . '/' . $anciennePhoto);
                }

                // Move the file to the directory where brochures are stored
                try {
                    $imgProfileEleve->move(
                        $this->getParameter('photoEleve_directory'),
                        $newFileNameImg
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu. Veuillez retransférer votre fichier !");
                }

                $eleve->setPhotoEleve($newFileNameImg);
            }

            $entityManager->flush();

            $this->addFlash(
                'SuccessEleve',
                'L\'élève a été modifié !'
            );

            return $this->redirectToRoute('eleve_edit', array('id' => $eleve->getId()));
        }

        return $this->render('eleve/edit.html.twig', [
            'eleve' => $eleve,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="eleve_delete_view", methods={"GET"})
     */
    public function delete_view(Eleve $eleve): Response
    {
        return $this->render('eleve/delete_view.html.twig', [
            'eleve' => $eleve,
        ]);
    }

    /**
     * @Route("/{id}/delete", name="eleve_delete", methods={"POST"})
     * @throws NonUniqueResultException
     */
    public function delete(Request                      $request,
                           Eleve                        $eleve,
                           EntityManagerInterface       $entityManager,
                           UserRepository               $userRepo,
                           InscriptionCantineRepository $cantineRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $eleve->getId(), $request->request->get('_token'))) {

            if ($eleve->getPhotoEleve() != null) {
                unlink($this->getParameter('photoEleve_directory') . '/' . $eleve->getPhotoEleve());
            }
            $user = $userRepo->findOneByEleve($eleve->getId());
            $cantine = $cantineRepository->findOneByEleve($eleve->getId());
            if ($user) {
                $entityManager->remove($user);
            }
            if ($cantine) {
                $entityManager->remove($cantine);
            }
            $entityManager->remove($eleve);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteEleve',
                'L\'élève a été supprimé !'
            );
        }

        return $this->redirectToRoute('eleve_index');
    }
}
