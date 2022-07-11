<?php

namespace App\Controller;

use Ang3\Component\Serializer\Encoder\ExcelEncoder;
use App\Entity\Adulte;
use App\Entity\Fichier;
use App\Form\AdulteType;
use App\Form\FichierType;
use App\Form\FilterOrSearch\FilterAdulteType;
use App\Repository\AdulteRepository;
use App\Repository\UserRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Picqer\Barcode\BarcodeGeneratorPNG;
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
 * @Route("/adulte")
 */
class AdulteController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AdulteRepository $adulteRepo;

    public function __construct(EntityManagerInterface $entityManager,
                                AdulteRepository       $adulteRepo)
    {
        $this->entityManager = $entityManager;
        $this->adulteRepo = $adulteRepo;
    }

    /**
     * Page de gestion des adultes
     * @Route("/index/{page}",defaults={"page" : 1}, name="adulte_index", methods={"GET","POST"})
     * @param AdulteRepository $adulteRepo
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param int $page Utilisé dans les filtres et la pagination
     * @return Response
     */
    public function index(AdulteRepository   $adulteRepo,
                          Request            $request,
                          PaginatorInterface $paginator,
                          int                $page = 1): Response
    {
        /*Récupération des adultes non archivés*/
        $adultes = $adulteRepo->findByArchive(false);
        $form = $this->createForm(FilterAdulteType::class, null, ['method' => 'GET']);
        $filter = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $adultes = $adulteRepo->filter(
                $filter->get('nomAdulte')->getData(),
                $filter->get('ordreNom')->getData(),
                $filter->get('ordrePrenom')->getData(),
                $filter->get('archiveAdulte')->getData()
            );
        }
        /*Pagination des adultes*/
        $adultes = $paginator->paginate(
            $adultes,
            $page,
            20
        );

        return $this->render('adulte/index.html.twig', [
            'adultes' => $adultes,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire d'ajout une liste d'adultes à partir d'excel
     * @Route("/file", name="adulte_file", methods={"GET","POST"})
     * @param Request $request
     * @param SluggerInterface $slugger
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function fileSubmit(Request                $request,
                               SluggerInterface       $slugger,
                               EntityManagerInterface $entityManager): Response
    {
        $adulteFile = new Fichier();
        $form = $this->createForm(FichierType::class, $adulteFile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichierAdulte */
            $fichierAdulte = $form->get('fileSubmit')->getData();
            /*Vérification d'ajout d'un fichier excel*/
            if ($fichierAdulte) {
                $originalFilename = pathinfo($fichierAdulte->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '.' . $fichierAdulte->guessExtension();
                try {
                    $fichierAdulte->move(
                        $this->getParameter('adulteFile_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu. Veuillez retransférer votre liste !");
                }
                $adulteFile->setFileName($newFilename);
            }

            $entityManager->persist($adulteFile);
            $entityManager->flush();

            /*Traitement du fichier excel*/
            AdulteController::creerAdulte($adulteFile->getFileName());

            /*Message de validation*/
            $this->addFlash(
                'SuccessAdulteFileSubmit',
                'Les adultes ont été sauvegardés !'
            );

            return $this->redirectToRoute('adulte_file');
        }

        return $this->render('adulte/adulteFile.html.twig', [
            'fichierAdulte' => $adulteFile,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Fonction permettant de traiter le fichier excel
     * @param string $fileName
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function creerAdulte(string $fileName): void
    {
        $adulteCreated = 0;
        $adulteNonArchives = $this->adulteRepo->findByArchive(false);
        /* Parcours le tableau donné par le fichier Excel*/
        while ($adulteCreated < sizeof($this->getDataFromFile($fileName))) {
            /*Pour chaque adulte*/
            foreach ($this->getDataFromFile($fileName) as $row) {
                /*Parcours les données d'un adulte */
                foreach ($row as $rowData) {
                    /*Vérifie s'il existe une colonne Nom et qu'elle n'est pas vide*/
                    if (array_key_exists('Nom', $rowData) && !empty($rowData['Nom']) && $rowData['Nom'] != 'Nom') {
                        if (!empty($rowData['Date de naissance'])) {
                            $adulteRelated = $this->adulteRepo->findByNomPrenomDateNaissance($rowData['Nom'],
                                $rowData['Prénom'], new DateTime($rowData['Date de naissance'],
                                    new DateTimeZone('Europe/Paris')));
                        } else {
                            $adulteRelated = $this->adulteRepo->findByNomPrenom($rowData['Nom'],
                                $rowData['Prénom']);
                        }

                        /*Vérifie si l'adulte dans le fichier est dans le tableau des non archivé*/
                        if (in_array($adulteRelated, $adulteNonArchives)) {
                            /*Enlève dans le tableau des non archivé les adultes
                             qui sont dans le fichier excel*/
                            if (($key = array_search($adulteRelated, $adulteNonArchives)) !== false) {
                                unset($adulteNonArchives[$key]);
                            }
                        }
                        /*Générer un code barre pour l'adulte*/
                        $generator = new BarcodeGeneratorPNG();

                        /*Vérifie si l'adulte a un numéro de badge*/
                        if ($rowData['N° de Badge'] != null) {

                            /*Si oui, on nomme le fichier qui contiendra l'image du code barre*/
                            $codeBar = 'code_' . $rowData['Nom'] . '_' . $rowData['Prénom'] . '.png';

                            /*
                              Puis met dans l'emplacement du fichier,
                              on génère l'image du code barre grâce au numéro de badge de l'adulte
                            */
                            file_put_contents($this->getParameter('codeBarAdulteFile_directory') . $codeBar,
                                $generator->getBarcode($rowData['N° de Badge'],
                                    $generator::TYPE_CODE_128, 3, 100));
                        } else {
                            /*Sinon on met le code barre à null*/
                            $codeBar = null;
                        }

                        /*Si l'adulte saisi existe alors il est modifié*/
                        if ($adulteRelated !== null) {
                            $adulteRelated
                                ->setPrenomAdulte($rowData['Prénom'])
                                ->setNomAdulte($rowData['Nom'])
                                ->setArchiveAdulte(false);

                            if ($rowData['Date de naissance'] != null) {
                                $adulteRelated->setDateNaissance(new DateTime($rowData['Date de naissance'],
                                    new DateTimeZone('Europe/Paris')));
                            }
                            $adulteRelated->setCodeBarreAdulte($codeBar);
                        } else {
                            /*Sinon il est créé*/
                            $adulte = new Adulte();
                            $adulte
                                ->setPrenomAdulte($rowData['Prénom'])
                                ->setNomAdulte($rowData['Nom'])
                                ->setArchiveAdulte(false);

                            if ($rowData['Date de naissance'] != null) {
                                $adulte->setDateNaissance(new DateTime($rowData['Date de naissance'],
                                    new DateTimeZone('Europe/Paris')));
                            }
                            $adulte->setCodeBarreAdulte($codeBar);
                            $this->entityManager->persist($adulte);
                        }
                    }
                    $adulteCreated++;
                }
            }
        }

        /*Reste que tous les adultes non archivés qui ont quitté l'établissement*/
        foreach ($adulteNonArchives as $adulte) {
            $adulte
                ->setArchiveAdulte(true);
            $this->entityManager->persist($adulte);
        }
        $this->entityManager->flush();
    }

    /**
     * Fonction permettant de récupérer les données du fichier excel et de retourner
     * un tableau qui contient les adultes dans l'excel
     * @param string $fileName
     * @return array
     */
    public function getDataFromFile(string $fileName): array
    {
        $file = $this->getParameter('adulteFile_directory') . $fileName;

        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

        $normalizers = [new ObjectNormalizer()];

        $encoders = [
            new ExcelEncoder($defaultContext = []),
        ];

        $serializer = new Serializer($normalizers, $encoders);

        /** @var string $fileString */
        $fileString = file_get_contents($file);

        /*Réfactorisation des colonnes du fichier Excel */
        $dataRaw = $serializer->decode($fileString, $fileExtension);
        $data = [];
        /*Pour chaque ligne de Feuil1 dans l'excel */
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
                        "N° de Badge" => $row[""][3]
                    ];
                } elseif (key($row) == "Nom") {
                    /*
                      Deuxième cas de figure : l'excel a uniquement la légende
                      et les données et rempli les données dans un tableau
                     */
                    $temp1 = [
                        "Nom" => $row["Nom"],
                    ];
                    $temp2 = [
                        "Prénom" => $row["Prénom"],
                        "Date de naissance" => $row["Date de naissance"],
                        "N° de Badge" => $row["N° de Badge"]
                    ];
                } else {
                    /*
                      Troisième cas de figure : l'excel a des lignes avec des données
                      écites avant la légende (exemple une date)
                      et rempli les données dans un tableau
                     */
                    $temp1 = [
                        "Nom" => $row[key($row)],
                    ];
                    $temp2 = [
                        "Prénom" => $row[""][1],
                        "Date de naissance" => $row[""][2],
                        "N° de Badge" => $row[""][3]
                    ];
                }
                /*Rassemble les deux tableaux*/
                $data[][] = array_merge($temp1, $temp2);
            }
        }
        return $data;
    }

    /**
     * Formulaire d'ajout d'un adulte
     * @Route("/new", name="adulte_new", methods={"GET", "POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $adulte = new Adulte();
        $form = $this->createForm(AdulteType::class, $adulte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($adulte);
            $entityManager->flush();
            $this->addFlash(
                'SuccessAdulte',
                'L\'adulte a été sauvegardé !'
            );
            return $this->redirectToRoute('adulte_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('adulte/new.html.twig', [
            'adulte' => $adulte,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de modificiation d'un adulte
     * @Route("/{id}/edit", name="adulte_edit", methods={"GET", "POST"})
     * @param Request $request
     * @param Adulte $adulte
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Request                $request,
                         Adulte                 $adulte,
                         EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdulteType::class, $adulte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash(
                'SuccessAdulte',
                'L\'adulte a été modifié !'
            );
            return $this->redirectToRoute('adulte_edit', ['id' => $adulte->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('adulte/edit.html.twig', [
            'adulte' => $adulte,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de pré-suppression d'un adulte
     * @Route("/{id}/delete_view", name="adulte_delete_view", methods={"GET"})
     * @param Adulte $adulte
     * @return Response
     */
    public function delete_view(Adulte $adulte): Response
    {
        return $this->render('adulte/delete_view.html.twig', [
            'adulte' => $adulte,
        ]);
    }

    /**
     * Formulaire de suppression d'un adulte
     * @Route("/{id}", name="adulte_delete", methods={"POST"})
     * @param Request $request
     * @param Adulte $adulte
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function delete(Request                $request,
                           Adulte                 $adulte,
                           EntityManagerInterface $entityManager,
                           UserRepository         $userRepo): Response
    {
        if ($this->isCsrfTokenValid('delete' . $adulte->getId(), $request->request->get('_token'))) {
            /*Récuperation du compte utilisateur de l'adulte et le supprime*/
            $user = $userRepo->findOneByAdulte($adulte->getId());
            /*Si l'adulte a un compte utilisateur alors le compte est supprimé*/
            if ($user) {
                $entityManager->remove($user);
            }
            $entityManager->remove($adulte);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteAdulte',
                'L\'adulte a été supprimé !'
            );
        }

        return $this->redirectToRoute('adulte_index', [], Response::HTTP_SEE_OTHER);
    }
}
