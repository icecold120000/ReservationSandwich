<?php

namespace App\Controller;

use Ang3\Component\Serializer\Encoder\ExcelEncoder;
use App\Entity\Fichier;
use App\Entity\InscriptionCantine;
use App\Form\FichierType;
use App\Repository\EleveRepository;
use App\Repository\InscriptionCantineRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/inscription/cantine")
 */
class InscriptionCantineController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EleveRepository $eleveRepository;
    private InscriptionCantineRepository $inscritCantRepo;

    public function __construct(EntityManagerInterface       $entityManager,
                                EleveRepository              $eleveRepository,
                                InscriptionCantineRepository $inscritCantRepo)
    {
        $this->entityManager = $entityManager;
        $this->eleveRepository = $eleveRepository;
        $this->inscritCantRepo = $inscritCantRepo;
    }

    /**
     * @Route("/", name="inscription_cantine_index", methods={"GET","POST"})
     * @throws NonUniqueResultException
     */
    public function fileSubmit(Request                $request,
                               SluggerInterface       $slugger,
                               EntityManagerInterface $entityManager): Response
    {
        $cantineFile = new Fichier();
        $form = $this->createForm(FichierType::class, $cantineFile);
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
                        $this->getParameter('cantineFile_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu. Veuillez retransférer votre liste !");
                }

                $cantineFile->setFileName($newFilename);
            }
            // Modifie la propriété 'filename' pour stocker le nom du fichier XLSX
            $entityManager->persist($cantineFile);
            $entityManager->flush();

            // Traite les données du fichier Excel et l'envoie dans la base de donnée
            InscriptionCantineController::creerCantine($cantineFile->getFileName());

            // Affiche le message de validation
            $this->addFlash(
                'SuccessFileSubmit',
                'Vos inscriptions ont été sauvegardées ou modifiées !'
            );

            return $this->redirectToRoute('inscription_cantine_file');
        }

        return $this->render('inscription_cantine/cantineFile.html.twig', [
            'fichierUser' => $cantineFile,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function creerCantine(string $fileName): void
    {
        $cantineCount = 0;
        $cantineNonArchive = $this->inscritCantRepo->findByArchive(false);
        /* Parcours le tableau donné par le fichier Excel*/
        while ($cantineCount < sizeof($this->getDataFromFile($fileName))) {
            /*Pour chaque élève*/
            foreach ($this->getDataFromFile($fileName) as $row) {
                /*Parcours les données d'une inscription*/
                foreach ($row as $rowData) {

                    /*Vérifie s'il existe une colonne nom et qu'elle n'est pas vide*/
                    if (array_key_exists('Nom', $rowData)
                        && !empty($rowData['Nom'])) {
                        $birthday = null;
                        if (array_key_exists('Date de naissance', $rowData)
                            && !empty($rowData['Date de naissance'])) {
                            $birthday = new DateTime($rowData['Date de naissance']);
                        }

                        /*Recherche l'élève dans la base de donnée*/
                        $eleveExcel = $this->eleveRepository->findByNomPrenomDateNaissance($rowData['Nom'],
                            $rowData['Prénom'],
                            $birthday
                        );

                        $cantineExcel = $this->inscritCantRepo->findOneBy(['eleve' => $eleveExcel]);

                        /*Vérifie si l'adulte dans le fichier est dans le tableau des non archivé*/
                        if (in_array($cantineExcel, $cantineNonArchive)) {

                            /*Enlève dans le tableau des non archivé les adultes
                             qui sont dans le fichier excel*/
                            if (($key = array_search($cantineExcel, $cantineNonArchive)) !== false) {
                                unset($cantineNonArchive[$key]);
                            }
                        }

                        /* Si l'élève est trouvé et doit être modifié*/
                        if ($cantineExcel !== Null) {
                            $cantineExcel->setRepasJ1(!empty($rowData['Repas Midi J1']))
                                ->setRepasJ2(!empty($rowData['Repas Midi J2']))
                                ->setRepasJ3(!empty($rowData['Repas Midi J3']))
                                ->setRepasJ4(!empty($rowData['Repas Midi J4']))
                                ->setRepasJ5(!empty($rowData['Repas Midi J5']));
                            $this->entityManager->persist($cantineExcel);

                        } /*S'il n'existe pas alors on le crée
                         en tant qu'un nouvel élève*/
                        else {
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

                        $cantineCount++;
                    }
                }
            }
        }
        /*Reste que toutes les inscriptions non archivées où les élèves ont quitté l'établissement*/
        foreach ($cantineNonArchive as $inscription) {
            $inscription->setArchiveInscription(true);

            $this->entityManager->persist($inscription);
        }
        $this->entityManager->flush();
    }

    public function getDataFromFile(string $fileName): array
    {
        $file = $this->getParameter('cantineFile_directory') . '/' . $fileName;

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

}
