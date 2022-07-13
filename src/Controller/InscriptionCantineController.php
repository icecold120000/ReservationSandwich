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
     * Formulaire d'ajout d'une liste d'inscription à la cantine
     * @Route("/index", name="inscription_cantine_index", methods={"GET","POST"})
     * @param Request $request
     * @param SluggerInterface $slugger
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws NonUniqueResultException
     */
    public function fileSubmit(Request                $request,
                               SluggerInterface       $slugger,
                               EntityManagerInterface $entityManager): Response
    {
        /*Création d'un tableau qui contiendra les élèves qui n'ont pas été enregistrées*/
        $eleveMissing = [];
        $cantineFile = new Fichier();
        $form = $this->createForm(FichierType::class, $cantineFile);
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichierEleve */
            $fichierEleve = $form->get('fileSubmit')->getData();
            /*Vérifie si le champ a été rempli*/
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

            // Traite les données du fichier Excel, l'envoie dans la base de donnée
            // retourne des élèves manquants
            $eleveMissing = $this->creerCantine($cantineFile->getFileName());

            // Affiche le message de validation
            $this->addFlash(
                'SuccessCantineFileSubmit',
                'Les inscriptions ont été sauvegardées ou modifiées !'
            );

            return $this->render('inscription_cantine/cantineFile.html.twig', [
                'fichierUser' => $cantineFile,
                'form' => $form->createView(),
                'eleveMissingTab' => $eleveMissing,
            ]);
        }


        return $this->render('inscription_cantine/cantineFile.html.twig', [
            'fichierUser' => $cantineFile,
            'form' => $form->createView(),
            'eleveMissingTab' => $eleveMissing,
        ]);
    }

    /**
     * Fonction permettant de traiter les inscriptions à la cantine
     * des élèves
     * @param string $fileName
     * @return array|null
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function creerCantine(string $fileName): array|null
    {
        $cantineCount = 0;
        $eleveMissingBdd = [];
        $i = 0;
        $cantineNonArchive = $this->inscritCantRepo->findByArchive(false);
        /* Parcours le tableau donné par le fichier Excel*/
        while ($cantineCount < sizeof($this->getDataFromFile($fileName))) {
            /*Pour chaque élève*/
            foreach ($this->getDataFromFile($fileName) as $row) {
                /*Parcours les données d'une inscription*/
                foreach ($row as $rowData) {
                    /*Vérifie s'il existe une colonne nom et qu'elle n'est pas vide*/
                    if (array_key_exists('Nom', $rowData) && !empty($rowData['Nom']) && $rowData['Nom'] != 'Nom') {
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
                        /*Si l'élève existe */
                        if ($eleveExcel != null) {
                            $cantineExcel = $this->inscritCantRepo->findOneBy(['eleve' => $eleveExcel]);
                            /*Vérifie si l'élève dans le fichier est dans le tableau des non archivé*/
                            if (in_array($cantineExcel, $cantineNonArchive)) {
                                /*Enlève dans le tableau des non archivé les élèves
                                 qui sont dans le fichier excel*/
                                if (($key = array_search($cantineExcel, $cantineNonArchive)) !== false) {
                                    unset($cantineNonArchive[$key]);
                                }
                            }
                            /* Si l'inscription est trouvée et doit être modifiée*/
                            if ($cantineExcel !== null) {
                                $cantineExcel->setRepasJ1(!empty($rowData['Repas Midi J1']))
                                    ->setRepasJ2(!empty($rowData['Repas Midi J2']))
                                    ->setRepasJ3(!empty($rowData['Repas Midi J3']))
                                    ->setRepasJ4(!empty($rowData['Repas Midi J4']))
                                    ->setRepasJ5(!empty($rowData['Repas Midi J5']));

                            } /*S'il n'existe pas alors on le crée en tant qu'une nouvelle inscription à la cantine pour cette élève*/
                            else {
                                /*Vérifie si le nombre de repas est supérieur à zéro */
                                if ($eleveExcel->getNbRepas() > 0) {
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
                            }
                        } else {
                            /*Récupère les élèves manquants*/
                            $eleveMissingBdd[$i] = ["prenom" => $rowData['Prénom'], "nom" => $rowData['Nom'], "dateDeNaissance" => $rowData['Date de naissance']];
                            $i++;
                        }
                    }
                    $cantineCount++;
                }
            }
        }
        /*Reste que toutes les inscriptions non archivées où les élèves ont quitté l'établissement*/
        foreach ($cantineNonArchive as $inscription) {
            $inscription->setArchiveInscription(true);
        }

        $this->entityManager->flush();
        /*Affiche un message d'erreur et la liste des élèves manquants*/
        if ($eleveMissingBdd != []) {
            $this->addFlash(
                'eleveMissing',
                'Le(s) inscriptions de(s) élève(s) suivant(s) n\'ont pas été prise(s) en compte !
                Veuillez vérifié l\'orthographe, la date de naissance
                 ou la présence dans la base de données de(s) élève(s) suivant(s) :'

            );
            return $eleveMissingBdd;
        } else {
            return null;
        }
    }


    /**
     * Fonction permettant de récupérer les données du fichier excel et de retourner
     * un tableau qui contient les inscriptions à la cantine des élèves dans le fichier excel
     * @param string $fileName
     * @return array
     */
    public function getDataFromFile(string $fileName): array
    {
        $file = $this->getParameter('cantineFile_directory') . $fileName;

        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

        $normalizers = [new ObjectNormalizer()];

        $encoders = [
            new ExcelEncoder($defaultContext = []),
        ];

        $serializer = new Serializer($normalizers, $encoders);

        /** @var string $fileString */
        $fileString = file_get_contents($file);
        $dataRaw = $serializer->decode($fileString, $fileExtension);
        $data = [];
        foreach ($dataRaw['Feuil1'] as $row) {
            /*Vérifie si la clé de la ligne est different de null*/
            if (key($row) != null) {
                /*
                  Premier cas de figure : le fichier excel a des lignes vides
                  au-dessus de la ligne où sont marqué la légende
                  Vérifie si la clé a une valeur vide et
                  rempli les données dans un tableau
                 */
                if (key($row) == "") {
                    $temp1 = [
                        "Nom" => $row[""][0],
                    ];
                    $temp2 = [
                        "Prénom" => $row[""][1],
                        "Date de naissance" => $row[""][2],
                        "Nombre de repas Midi" => $row[""][3],
                        "Repas Midi J1" => $row[""][4],
                        "Repas Midi J2" => $row[""][5],
                        "Repas Midi J3" => $row[""][6],
                        "Repas Midi J4" => $row[""][7],
                        "Repas Midi J5" => $row[""][8]
                    ];
                } elseif (key($row) == "Nom") {
                    /*
                      Deuxième cas de figure : le fichier excel a uniquement la légende
                      et les données
                      et rempli les données dans un tableau
                     */
                    $temp1 = [
                        "Nom" => $row["Nom"],
                    ];
                    $temp2 = [
                        "Prénom" => $row["Prénom"],
                        "Date de naissance" => $row["Date de naissance"],
                        "Nombre de repas Midi" => $row["Nombre de repas Midi"],
                        "Repas Midi J1" => $row["Repas Midi J1"],
                        "Repas Midi J2" => $row["Repas Midi J2"],
                        "Repas Midi J3" => $row["Repas Midi J3"],
                        "Repas Midi J4" => $row["Repas Midi J4"],
                        "Repas Midi J5" => $row["Repas Midi J5"],
                    ];
                } else {
                    /*
                      Troisième cas de figure : le fichier excel a des lignes avec des données
                      écrites avant la légende (exemple une date)
                      et rempli les données dans un tableau
                     */
                    $temp1 = [
                        "Nom" => $row[key($row)],
                    ];
                    $temp2 = [
                        "Prénom" => $row[""][1],
                        "Date de naissance" => $row[""][2],
                        "Nombre de repas Midi" => $row[""][3],
                        "Repas Midi J1" => $row[""][4],
                        "Repas Midi J2" => $row[""][5],
                        "Repas Midi J3" => $row[""][6],
                        "Repas Midi J4" => $row[""][7],
                        "Repas Midi J5" => $row[""][8]
                    ];
                }
                $data[][] = array_merge($temp1, $temp2);
            }
        }
        return $data;
    }
}
