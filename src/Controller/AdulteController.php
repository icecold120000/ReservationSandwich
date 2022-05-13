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
     * @Route("/", name="adulte_index", methods={"GET","POST"})
     */
    public function index(AdulteRepository   $adulteRepo,
                          Request            $request,
                          PaginatorInterface $paginator): Response
    {
        $adultes = $adulteRepo->findByArchive(false);
        $form = $this->createForm(FilterAdulteType::class);
        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adultes = $adulteRepo->filter(
                $filter->get('nomAdulte')->getData(),
                $filter->get('ordreNom')->getData(),
                $filter->get('ordrePrenom')->getData(),
                $filter->get('archiveAdulte')->getData()
            );
        }

        $adultes = $paginator->paginate(
            $adultes,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('adulte/index.html.twig', [
            'adultes' => $adultes,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/file", name="adulte_file", methods={"GET","POST"})
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

            AdulteController::creerAdulte($adulteFile->getFileName());

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
                        $generator = new BarcodeGeneratorPNG();
                        $codeBar = 'code_' . $rowData['Nom'] . '_' . $rowData['Prénom'] . '.png';
                        if ($adulteRelated !== null) {
                            $adulteRelated->setPrenomAdulte($rowData['Prénom'])
                                ->setNomAdulte($rowData['Nom'])
                                ->setArchiveAdulte(false);

                            if ($rowData['Date de naissance'] != null) {
                                $adulteRelated->setDateNaissance(new DateTime($rowData['Date de naissance'],
                                    new DateTimeZone('Europe/Paris')));
                            }
                            $adulteRelated->setCodeBarreAdulte($codeBar);
                            $this->entityManager->persist($adulteRelated);
                        } else {
                            $adulte = new Adulte();

                            $adulte->setPrenomAdulte($rowData['Prénom'])
                                ->setNomAdulte($rowData['Nom'])
                                ->setArchiveAdulte(false);

                            if ($rowData['Date de naissance'] != null) {
                                $adulte->setDateNaissance(new DateTime($rowData['Date de naissanced'],
                                    new DateTimeZone('Europe/Paris')));
                            }
                            $adulte->setCodeBarreAdulte($codeBar);
                            $this->entityManager->persist($adulte);
                        }

                        file_put_contents($this->getParameter('codeBarAdulteFile_directory') . $codeBar,
                            $generator->getBarcode($rowData['N° de Badge'], $generator::TYPE_CODE_128, 3, 100));
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
        $dataRaw = $serializer->decode($fileString, $fileExtension);
        $data = [];
        foreach ($dataRaw['Feuil1'] as $row) {
            if (key($row) != null or key($row) == "") {
                if (key($row) == "") {
                    $temp1 = [
                        "Nom" => $row[""][0],
                    ];
                } else {
                    $temp1 = [
                        "Nom" => $row[key($row)],
                    ];
                }
                $temp2 = [
                    "Prénom" => $row[""][1],
                    "Date de naissance" => $row[""][2],
                    "N° de Badge" => $row[""][3]
                ];
                $data[][] = array_merge($temp1, $temp2);
            }
        }
        return $data;
    }

    /**
     * @Route("/new", name="adulte_new", methods={"GET", "POST"})
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

        return $this->renderForm('adulte/new.html.twig', [
            'adulte' => $adulte,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="adulte_show", methods={"GET"})
     */
    public function show(Adulte $adulte): Response
    {
        return $this->render('adulte/show.html.twig', [
            'adulte' => $adulte,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="adulte_edit", methods={"GET", "POST"})
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

        return $this->renderForm('adulte/edit.html.twig', [
            'adulte' => $adulte,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}/delete_view", name="adulte_delete_view", methods={"GET"})
     */
    public function delete_view(Adulte $adulte): Response
    {
        return $this->render('adulte/delete_view.html.twig', [
            'adulte' => $adulte,
        ]);
    }

    /**
     * @Route("/{id}", name="adulte_delete", methods={"POST"})
     * @throws NonUniqueResultException
     */
    public function delete(Request                $request,
                           Adulte                 $adulte,
                           EntityManagerInterface $entityManager,
                           UserRepository         $userRepo): Response
    {
        if ($this->isCsrfTokenValid('delete' . $adulte->getId(), $request->request->get('_token'))) {
            $user = $userRepo->findOneByAdulte($adulte->getId());
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
