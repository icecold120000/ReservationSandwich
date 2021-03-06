<?php

namespace App\Controller;

use Ang3\Component\Serializer\Encoder\ExcelEncoder;
use App\Entity\CommandeIndividuelle;
use App\Entity\DesactivationCommande;
use App\Entity\User;
use App\Form\CommandeIndividuelleType;
use App\Form\FilterOrSearch\FilterAdminCommandeType;
use App\Form\FilterOrSearch\FilterExportationType;
use App\Form\FilterOrSearch\FilterIndexCommandeType;
use App\Repository\BoissonRepository;
use App\Repository\CommandeGroupeRepository;
use App\Repository\CommandeIndividuelleRepository;
use App\Repository\DesactivationCommandeRepository;
use App\Repository\DessertRepository;
use App\Repository\EleveRepository;
use App\Repository\InscriptionCantineRepository;
use App\Repository\LimitationCommandeRepository;
use App\Repository\SandwichRepository;
use App\Repository\UserRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf as Dompdf;
use Dompdf\Options as OptionsPdf;

/**
 * @Route("/commande/individuelle")
 */
class CommandeIndividuelleController extends AbstractController
{
    private SandwichRepository $sandwichRepo;
    private BoissonRepository $boissonRepo;
    private DessertRepository $dessertRepo;
    private CommandeIndividuelleRepository $comIndRepo;
    private EleveRepository $eleveRepo;
    private CommandeGroupeRepository $comGrRepo;

    public function __construct(SandwichRepository             $sandwichRepo,
                                BoissonRepository              $boissonRepo,
                                DessertRepository              $dessertRepo,
                                CommandeIndividuelleRepository $comIndRepo,
                                EleveRepository                $eleveRepo,
                                CommandeGroupeRepository       $comGrRepo)
    {
        $this->sandwichRepo = $sandwichRepo;
        $this->boissonRepo = $boissonRepo;
        $this->dessertRepo = $dessertRepo;
        $this->comIndRepo = $comIndRepo;
        $this->eleveRepo = $eleveRepo;
        $this->comGrRepo = $comGrRepo;
    }

    /**
     * Historique de commandes
     * @Route("/index/{comIndPage}/{comGrPage}",defaults={"comIndPage" : 1,"comGrPage" : 1}, name="commande_individuelle_index", methods={"GET","POST"})
     * @param CommandeIndividuelleRepository $comIndRepo
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @param LimitationCommandeRepository $limiteRepo
     * @param CommandeGroupeRepository $comGrRepo
     * @param UserRepository $userRepo
     * @param int $comIndPage Utilis?? pour le filtre et la pagination des commandes individuelles
     * @param int $comGrPage Utilis?? pour le filtre et la pagination des commandes group??es
     * @return Response
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function index(CommandeIndividuelleRepository $comIndRepo,
                          PaginatorInterface             $paginator,
                          Request                        $request,
                          LimitationCommandeRepository   $limiteRepo,
                          CommandeGroupeRepository       $comGrRepo,
                          UserRepository                 $userRepo,
                          int                            $comIndPage = 1,
                          int                            $comGrPage = 1): Response
    {
        /*R??cup??re l'utilisateur courant*/
        $user = $userRepo->find($this->getUser());

        /*Affichage par d??faut de la page*/
        $affichageTableau = "les deux";

        /*R??cup??ration des limites mise en place sur les commandes*/
        $limiteGroupeCom = $limiteRepo->findOneById(5);
        $limiteJourMeme = $limiteRepo->findOneById(1);
        $limiteNbJour = $limiteRepo->findOneById(2);
        $limiteNbSemaine = $limiteRepo->findOneById(3);
        $limiteNbMois = $limiteRepo->findOneById(4);
        $nbCommandeJournalier = count($comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 day 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeSemaine = count($comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 week 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeMois = count($comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 month 23:59:00', new DateTimezone('Europe/Paris'))));
        $limiteDate = new DateTime('now' . $limiteJourMeme->getHeureLimite()->format('h:i'),
            new DateTimeZone('Europe/Paris'));

        /*R??cup??re les commandes de l'utilisateur*/
        $commandes = $comIndRepo->findIndexAllNonCloture($user);
        $commandesGroupe = $comGrRepo->findAllIndexNonClotureGroupe($user);

        $form = $this->createForm(FilterIndexCommandeType::class, null, ['method' => 'GET']);
        $filter = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $commandes = $this->comIndRepo->filterIndex(
                $user,
                $filter->get('date')->getData(),
                $filter->get('cloture')->getData()
            );

            $affichageTableau = $filter->get('affichageTableau')->getData();
            $commandesGroupe = $comGrRepo->filterIndex(
                $user,
                $filter->get('date')->getData(),
                $filter->get('cloture')->getData()
            );
        }

        /*Pagination des commandes*/
        $commandes = $paginator->paginate(
            $commandes,
            $comIndPage,
            25,
            ['pageParameterName' => 'comIndPage']
        );

        /*Pagination des commandes group??es*/
        $commandesGroupe = $paginator->paginate(
            $commandesGroupe,
            $comGrPage,
            5,
            ['pageParameterName' => 'comGrPage']
        );

        return $this->render('commande_individuelle/index.html.twig', [
            'commandes_ind' => $commandes,
            'form' => $form->createView(),
            'limite' => $limiteDate,
            'limiteActive' => $limiteJourMeme->getIsActive(),
            'limiteNbJournalier' => $limiteNbJour->getNbLimite(),
            'limiteActiveNbJour' => $limiteNbJour->getIsActive(),
            'limiteNbSemaine' => $limiteNbSemaine->getNbLimite(),
            'limiteActiveNbSemaine' => $limiteNbSemaine->getIsActive(),
            'limiteNbMois' => $limiteNbMois->getNbLimite(),
            'limiteActiveNbMois' => $limiteNbMois->getIsActive(),
            'nbCommandeJournalier' => $nbCommandeJournalier,
            'nbCommandeSemaine' => $nbCommandeSemaine,
            'nbCommandeMois' => $nbCommandeMois,
            'commande_groupes' => $commandesGroupe,
            'affichageTableau' => $affichageTableau,
            'limiteGroupeCom' => $limiteGroupeCom,
            'limiteGroupeComActive' => $limiteGroupeCom->getIsActive(),
        ]);
    }

    /**
     * Formulaire permettant de d??sactiver et r??activer le service de commande
     * @Route("/desactivation/{desactiveId}", name="commande_ind_desactive", methods={"GET","POST"})
     * @Entity("desactivationCommande", expr="repository.find(desactiveId)")
     * @param DesactivationCommande $desactiveId
     * @param EntityManagerInterface $manager
     * @return RedirectResponse
     */
    public function deactivation(DesactivationCommande  $desactiveId,
                                 EntityManagerInterface $manager): RedirectResponse
    {
        if ($desactiveId->getIsDeactivated() === false) {
            $desactiveId->setIsDeactivated(true);
            $this->addFlash(
                'SuccessDeactivation',
                'Les pages de r??servations ont ??t?? d??sactiv??es !'
            );
        } elseif ($desactiveId->getIsDeactivated() === true) {
            $desactiveId->setIsDeactivated(false);
            $this->addFlash(
                'SuccessDeactivation',
                'Les pages de r??servations ont ??t?? r??activ??es !'
            );
        }

        $manager->persist($desactiveId);
        $manager->flush();

        return $this->redirectToRoute('espace_admin', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Gestion des commandes
     * @Route("/admin/{comIndPage}/{comGrPage}",defaults={"comIndPage" : 1,"comGrPage" : 1},
     *      name="commande_individuelle_admin", methods={"GET","POST"})
     * @param CommandeIndividuelleRepository $comIndRepo
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @param CommandeGroupeRepository $comGrRepo
     * @param int $comIndPage Utilis?? pour le filtre et la pagination des commandes individuelles
     * @param int $comGrPage Utilis?? pour le filtre et la pagination des commandes group??es
     * @return Response
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function admin(CommandeIndividuelleRepository $comIndRepo,
                          PaginatorInterface             $paginator,
                          Request                        $request,
                          CommandeGroupeRepository       $comGrRepo,
                          int                            $comIndPage = 1,
                          int                            $comGrPage = 1): Response
    {
        /*R??cup??ration des commandes + affichage par d??faut de la page*/
        $affichageTableau = "les deux";
        $commandes = $comIndRepo->findAllNonCloture();
        $commandesGroupe = $comGrRepo->findAllAdminNonClotureGroupe();

        /*Formulaire d'exportation de commandes*/
        $export = $this->createForm(FilterExportationType::class);
        $exportReq = $export->handleRequest($request);

        if ($export->isSubmitted() && $export->isValid()) {
            /*R??cup??ration et formatage de la date choisi par l'utilisateur*/
            $dateChoisi = $exportReq->get('dateExport')->getData();
            $dateChoisi = $dateChoisi->format('y-m-d');

            /*R??cup??ration de l'affichage du rendu*/
            $modalite = $exportReq->get('modaliteCommande')->getData();

            /*R??cup??ration des commandes et commandes group??es selon l'affichage de l'export*/
            if ($exportReq->get('affichageExport')->getData() == "les deux" ||
                $exportReq->get('affichageExport')->getData() == "individuelle") {
                $commandesExport = $comIndRepo->exportationCommande($dateChoisi);
            } else {
                $commandesExport = null;
            }
            if ($exportReq->get('affichageExport')->getData() == "les deux" ||
                $exportReq->get('affichageExport')->getData() == "group??") {
                $commandesGroupeExport = $comGrRepo->exportationCommandeGroupe($dateChoisi);
            } else {
                $commandesGroupeExport = null;
            }

            /*R??cup??ration du type rendu attendu par l'utilisateur (PDF, Excel ou Impression)*/
            $methode = $exportReq->get('methodeExport')->getData();
            if ($methode == "PDF") {
                /*Fonction permettant de mettre en pdf les commandes*/
                CommandeIndividuelleController::pdfDownload($commandesExport, $commandesGroupeExport, $modalite, $exportReq->get('dateExport')->getData());
            } elseif ($methode == "Excel") {
                /*V??rifie si le rendu attendu est les commandes sont affich??es un par un*/
                if ($modalite == "S??par??es") {
                    $commandeRow = [];
                    $commandeGroupeRow = [];
                    /*V??rifie s'il y a des commandes individuelles a export??*/
                    if ($commandesExport) {
                        /*Pour chaque commande*/
                        foreach ($commandesExport as $commande) {
                            /*V??rifie si la commande est faite par un ??l??ve et r??cup??re la classe de l'??l??ve
                             Sinon les adultes sont attribu??s une classe adulte
                            */
                            if (in_array(User::ROLE_ELEVE, $commande->getCommandeur()->getRoles())) {
                                $eleve = $this->eleveRepo->findOneByCompte($commande->getCommandeur());
                                $classe = $eleve->getClasseEleve()->getCodeClasse();
                            } else {
                                $classe = "Adulte";
                            }
                            /*R??cup??re si la commande a des chips command??s*/
                            if ($commande->getPrendreChips()) {
                                $chips = "Oui";
                            } else {
                                $chips = "Non";
                            }
                            /*Place les donn??es dans une ligne*/
                            $commandeRow[] = [
                                'Date et heure de Livraison' => $commande->getDateHeureLivraison()->format('d/m/y h:i'),
                                'Pr??nom et Nom' => $commande->getCommandeur()->getPrenomUser() . ' ' . $commande->getCommandeur()->getNomUser(),
                                'Classe' => $classe,
                                'Commande' => $commande->getSandwichChoisi()->getNomSandwich() . ', ' . $commande->getBoissonChoisie()->getNomBoisson() . ', ' . $commande->getDessertChoisi()->getNomDessert(),
                                'Chips' => $chips,
                            ];
                        }
                    }
                    if ($commandesGroupeExport) {
                        foreach ($commandesGroupeExport as $commandeGroupe) {
                            /*V??rifie si la commande est faite par un ??l??ve et r??cup??re la classe de l'??l??ve
                             Sinon les adultes sont attribu??s une classe adulte
                            */
                            if (in_array(User::ROLE_ELEVE, $commandeGroupe->getCommandeur()->getRoles())) {
                                $eleve = $this->eleveRepo->findOneByCompte($commandeGroupe->getCommandeur());
                                $classe = $eleve->getClasseEleve()->getCodeClasse();
                            } else {
                                $classe = "Adulte";
                            }

                            $sandwichsGroupe = [];
                            $nombreEleve = 0;
                            /*R??cup??re les sandwichs command??s*/
                            foreach ($commandeGroupe->getSandwichCommandeGroupes() as $sandwichChoisi) {
                                $nombreEleve = $nombreEleve + $sandwichChoisi->getNombreSandwich();
                                $sandwichsGroupe[] = $sandwichChoisi->getNombreSandwich() . ' ' . $sandwichChoisi->getSandwichChoisi()->getNomSandwich();
                            }
                            /*Place les donn??es dans une ligne*/
                            $commandeGroupeRow[] = [
                                'Date et heure de Livraison' => $commandeGroupe->getDateHeureLivraison()->format('d/m/y h:i'),
                                'Pr??nom et Nom' => $commandeGroupe->getCommandeur()->getPrenomUser() . ' ' . $commandeGroupe->getCommandeur()->getNomUser(),
                                'Classe' => $classe,
                                'Commande' => $sandwichsGroupe[0] . ', ' . $sandwichsGroupe[1] . ', ' . $nombreEleve . ' ' . $commandeGroupe->getBoissonChoisie()->getNomBoisson() . ', ' . $nombreEleve . ' ' . $commandeGroupe->getDessertChoisi()->getNomDessert(),
                                'Chips' => $nombreEleve . ' Chips',
                            ];
                        }
                    }

                    $encoder = new ExcelEncoder([]);
                    /*V??rifie s'il y a des commandes et des commandes group??es*/
                    if ($commandeGroupeRow != [] && $commandeRow != []) {
                        /*Regroupe les commandes et commandes group??es dans un m??me
                         tableau et le met dans un tableau qui sera dans une feuille excel
                        */
                        $commandesRegroupe = array_merge($commandeRow, $commandeGroupeRow);
                        $data = [
                            // Array by sheet
                            'Feuille 1' => $commandesRegroupe
                        ];
                    } else {
                        /*Met les commandes (group??es) dans un tableau qui sera dans une feuille excel*/
                        if ($commandeRow != []) {
                            $data = [
                                // Array by sheet
                                'Feuille 1' => $commandeRow
                            ];
                        } else {
                            $data = [
                                // Array by sheet
                                'Feuille 1' => $commandeGroupeRow
                            ];
                        }
                    }

                    // Encode data with specific format
                    $xls = $encoder->encode($data, ExcelEncoder::XLSX);
                    $dateChoisi = $exportReq->get('dateExport')->getData();

                    // Put the content in a file with format extension for example
                    file_put_contents('Commandes_S??par??es_' . $dateChoisi->format('d-m-y') . '.xlsx', $xls);
                    $filename = 'Commandes_S??par??es_' . $dateChoisi->format('d-m-y') . '.xlsx';
                    //Permet le t??l??chargement du fichier
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header("Cache-Control: no-cache, must-revalidate");
                    header("Expires: 0");
                    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                    header('Content-Length: ' . filesize($filename));
                    header('Pragma: public');

                    readfile($filename);
                    // D??place le fichier dans le dossier Uploads
                    rename($filename, $this->getParameter('exportFile_directory') . $filename);

                } elseif ($modalite == "Regroup??es") {
                    /*R??cup??re les produits disponibles*/
                    $sandwichDispo = $this->sandwichRepo->findByDispo(true);
                    $boissonDispo = $this->boissonRepo->findByDispo(true);
                    $dessertDispo = $this->dessertRepo->findByDispo(true);
                    $nomSandwich = [];
                    $nbSandwich = [];
                    $nomBoisson = [];
                    $nbBoisson = [];
                    $nomDessert = [];
                    $nbDessert = [];
                    $nbChips = 0;
                    /*Compte le nombre de sandwichs command??s pour chaque sandwich*/
                    foreach ($sandwichDispo as $sandwich) {
                        $nomSandwich[] = $sandwich->getNomSandwich();
                        $nombreSandwich = 0;
                        if ($commandesGroupeExport != null) {
                            foreach ($commandesGroupeExport as $commandeGroupe) {
                                foreach ($commandeGroupe->getSandwichCommandeGroupes() as $sandwichComGroupe) {
                                    if ($sandwichComGroupe->getSandwichChoisi()->getId() == $sandwich->getId()) {
                                        $nombreSandwich = $nombreSandwich + $sandwichComGroupe->getNombreSandwich();
                                    }
                                }
                            }
                        }
                        if ($commandesExport != null) {
                            $nbSandwich[] = count($comIndRepo->findBySandwich($sandwich->getId(), $dateChoisi)) + $nombreSandwich;
                        }
                    }
                    /*Compte le nombre de boissons command??s pour chaque boisson*/
                    foreach ($boissonDispo as $boisson) {
                        $nomBoisson[] = $boisson->getNomBoisson();
                        $nombreEleve = 0;
                        if ($commandesGroupeExport != null) {
                            foreach ($commandesGroupeExport as $commandeGroupe) {
                                foreach ($commandeGroupe->getSandwichCommandeGroupes() as $sandwichComGroupe) {
                                    if ($commandeGroupe->getBoissonChoisie()->getId() == $boisson->getId()) {
                                        $nombreEleve = $nombreEleve + $sandwichComGroupe->getNombreSandwich();
                                    }
                                }
                            }
                        }
                        if ($commandesExport != null) {
                            $nbBoisson[] = count($comIndRepo->findByBoisson($boisson->getId(), $dateChoisi)) + $nombreEleve;
                        }
                    }
                    /*Compte le nombre de desserts pour chaque dessert*/
                    foreach ($dessertDispo as $dessert) {
                        $nomDessert[] = $dessert->getNomDessert();
                        $nombreEleve = 0;
                        if ($commandesGroupeExport != null) {
                            foreach ($commandesGroupeExport as $commandeGroupe) {
                                foreach ($commandeGroupe->getSandwichCommandeGroupes() as $sandwichComGroupe) {
                                    if ($commandeGroupe->getDessertChoisi()->getId() == $dessert->getId()) {
                                        $nombreEleve = $nombreEleve + $sandwichComGroupe->getNombreSandwich();
                                    }
                                }
                            }
                            /*R??cup??re le nombre de chips pour les commandes group??es*/
                            $nbChips = $nbChips + $nombreEleve;
                        }
                        if ($commandesExport != null) {
                            $nbDessert[] = count($comIndRepo->findByDessert($dessert->getId(), $dateChoisi)) + $nombreEleve;
                        }
                    }

                    /*Place pour chaque sandwich, le nombre total de sandwich command??*/
                    $dataRowSandwich = [];
                    for ($i = 0; $i < count($nomSandwich); $i++) {
                        $dataRowSandwich[$i] = [
                            'Nom de produit' => $nomSandwich[$i],
                            'Nombre de produit' => $nbSandwich[$i],
                        ];
                    }

                    /*Place pour chaque boisson, le nombre total de boisson command??e*/
                    $dataRowBoisson = [];
                    for ($i = 0; $i < count($nomBoisson); $i++) {
                        $dataRowBoisson[$i] = [
                            'Nom de produit' => $nomBoisson[$i],
                            'Nombre de produit' => $nbBoisson[$i],
                        ];
                    }

                    /*Place pour chaque dessert, le nombre total de dessert command??*/
                    $dataRowDessert = [];
                    for ($i = 0; $i < count($nomDessert); $i++) {
                        $dataRowDessert[$i] = [
                            'Nom de produit' => $nomDessert[$i],
                            'Nombre de produit' => $nbDessert[$i],
                        ];
                    }
                    /*Compte le nombre de chips des commandes individuelles*/
                    if ($commandesExport) {
                        foreach ($commandesExport as $commande) {
                            if ($commande->getPrendreChips()) {
                                $nbChips++;
                            }
                        }
                    }
                    /*Place les donn??es dans un tableau*/
                    $dataRow = array_merge(
                        $dataRowSandwich,
                        $dataRowBoisson,
                        $dataRowDessert,
                        [
                            [
                                'Nom du produit' => 'Chips',
                                'Nombre de produit' => $nbChips,
                            ]
                        ]
                    );

                    $encoder = new ExcelEncoder([]);
                    /* Place les donn??es dans une feuille*/
                    $data = [
                        // Array by sheet
                        'Feuille 1' => $dataRow
                    ];

                    // Encode data with specific format
                    $xls = $encoder->encode($data, ExcelEncoder::XLSX);
                    $dateChoisi = $exportReq->get('dateExport')->getData();

                    // Put the content in a file with format extension for example
                    file_put_contents('Commandes_Regroup??es_' . $dateChoisi->format('d-m-y') . '.xlsx', $xls);
                    $filename = 'Commandes_Regroup??es_' . $dateChoisi->format('d-m-y') . '.xlsx';
                    /*Permet le t??l??chargement du fichier*/
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header("Cache-Control: no-cache, must-revalidate");
                    header("Expires: 0");
                    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                    header('Content-Length: ' . filesize($filename));
                    header('Pragma: public');
                    readfile($filename);
                    /*D??place le fichier dans le dossier Uploads*/
                    rename($filename, $this->getParameter('excelFile_directory') . $filename);
                }
                return new Response();
            } elseif ($methode == "Impression") {
                /*Ouvrir la page de pr??-impression avec les donn??es*/
                return CommandeIndividuelleController::printPreview($modalite,
                    $exportReq->get('dateExport')->getData(),
                    $exportReq->get('affichageExport')->getData());
            }

        } elseif ($export->isSubmitted()) {
            $this->addFlash(
                'failedExport',
                'Votre export a ??chou?? ?? la suite d\'une erreur !'
            );
        }
        /*Filtre de la page*/
        $form = $this->createForm(FilterAdminCommandeType::class, null, ['method' => 'GET']);
        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateFilter = $filter->get('date')->getData();
            if ($dateFilter != null) {
                $dateFilter->format('Y-m-d');
            } else {
                $dateFilter = null;
            }

            $commandes = $comIndRepo->filterAdmin(
                $filter->get('nom')->getData(),
                $dateFilter,
                $filter->get('cloture')->getData()
            );

            $affichageTableau = $filter->get('affichageTableau')->getData();
            $commandesGroupe = $comGrRepo->filterAdmin(
                $filter->get('nom')->getData(),
                $dateFilter,
                $filter->get('cloture')->getData()
            );
        }

        /*Pagination des commandes*/
        $commandes = $paginator->paginate(
            $commandes,
            $comIndPage,
            25,
            ['pageParameterName' => 'comIndPage']
        );

        /*Pagination des commandes group??es*/
        $commandesGroupe = $paginator->paginate(
            $commandesGroupe,
            $comGrPage,
            5,
            ['pageParameterName' => 'comGrPage']
        );

        return $this->render('commande_individuelle/admin.html.twig', [
            'commandes_ind' => $commandes,
            'commande_groupes' => $commandesGroupe,
            'form' => $form->createView(),
            'exportForm' => $export->createView(),
            'affichageTableau' => $affichageTableau,
        ]);
    }

    /**
     * Fonction permettant d'exporter les commandes sous forme de pdf
     * @Route("/pdf", name="commande_pdf", methods={"GET","POST"})
     * @param array $commandes
     * @param array $commandesGroupe
     * @param string $modalite
     * @param DateTime $dateChoisi
     * @return Response
     */
    public function pdfDownload(array  $commandes, array $commandesGroupe,
                                string $modalite, DateTime $dateChoisi): Response
    {
        // D??fini les options du pdf
        $optionsPdf = new OptionsPdf();

        // Donne une police par d??faut
        $optionsPdf->set('defaultFont', 'Arial');
        $optionsPdf->setIsRemoteEnabled(true);

        // Instancie Dompdf
        $dompdf = new Dompdf($optionsPdf);
        // Cr??er le context http du pdf
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        // Donne le context http au pdf
        $dompdf->setHttpContext($context);

        // G??n??re le pdf et le rendu html ?? partir du TWIG selon type de rendu choisi
        if ($modalite == "S??par??es") {
            $html = $this->renderView('commande_individuelle/pdf/commande_pdf_separe.html.twig', [
                'type' => "PDF",
                'commandes' => $commandes,
                'commandesGroupe' => $commandesGroupe,
                'dateChoisi' => $dateChoisi,
            ]);
        } else {
            $html = $this->renderView('commande_individuelle/pdf/commande_pdf_regroupe.html.twig', [
                'commandes' => $commandes,
                'commandesGroupe' => $commandesGroupe,
                'dateChoisi' => $dateChoisi,
                'sandwichDispo' => $this->sandwichRepo->findByDispo(true),
                'boissonDispo' => $this->boissonRepo->findByDispo(true),
                'dessertDispo' => $this->dessertRepo->findByDispo(true),
            ]);
        }

        // G??n??re l'affichage du pdf dans un onglet
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        $date = $dateChoisi->format('d-m-Y');

        // Nomme le fichier PDF
        $fichier = 'Commandes_' . $modalite . '_' . $date . '.pdf';

        // T??l??charge le pdf
        $dompdf->stream($fichier, [
            'Attachment' => true
        ]);

        // Retourne le r??sultat
        return new Response();
    }

    /**
     * Page de pr??-impression de l'exportation des commandes
     * @Route("/preview", name="commande_impression", methods={"GET","POST"})
     * @param string $modalite
     * @param DateTime $dateChoisi
     * @param string $affichage
     * @return Response
     */
    public function printPreview(string $modalite, DateTime $dateChoisi,
                                 string $affichage): Response
    {
        /*Si l'affichage choisi est individuelle ou les deux*/
        if ($affichage == "les deux" || $affichage == "individuelles") {
            /*Si oui, alors il y a r??cup??ration des commandes individuelles*/
            $commandes = $this->comIndRepo
                ->exportationCommande($dateChoisi->format('y-m-d'));
        } else {
            /*Sinon les commandes individuelles sont rendu null*/
            $commandes = null;
        }

        /*Si l'affichage choisi est group??es ou les deux*/
        if ($affichage == "les deux" || $affichage == "group??es") {
            /*Si oui, alors il y a r??cup??ration des commandes group??es*/
            $commandeGroupe = $this->comGrRepo->exportationCommandeGroupe($dateChoisi->format('y-m-d'));
        } else {
            /*Sinon les commandes group??es sont rendu null*/
            $commandeGroupe = null;
        }

        /*Affichage du rendu selon ce qui est demand??*/
        if ($modalite == "S??par??es") {
            return $this->render('commande_individuelle/pdf/commande_pdf_separe.html.twig', [
                'type' => "Impression",
                'commandes' => $commandes,
                'commandesGroupe' => $commandeGroupe,
                'dateChoisi' => $dateChoisi,
            ]);
        } else {
            return $this->render('commande_individuelle/pdf/commande_pdf_regroupe.html.twig', [
                'commandes' => $commandes,
                'dateChoisi' => $dateChoisi,
                'commandesGroupe' => $commandeGroupe,
                'sandwichDispo' => $this->sandwichRepo->findByDispo(true),
                'boissonDispo' => $this->boissonRepo->findByDispo(true),
                'dessertDispo' => $this->dessertRepo->findByDispo(true),
            ]);
        }
    }

    /**
     * Formulaire d'ajout d'une commande individuelle
     * @Route("/new", name="commande_individuelle_new", methods={"GET", "POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SandwichRepository $sandwichRepo
     * @param BoissonRepository $boissonRepo
     * @param DessertRepository $dessertRepo
     * @param DesactivationCommandeRepository $deactiveRepo
     * @param LimitationCommandeRepository $limiteRepo
     * @param InscriptionCantineRepository $cantineRepository
     * @param CommandeIndividuelleRepository $commandeRepo
     * @param EleveRepository $eleveRepository
     * @param UserRepository $userRepo
     * @return Response
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function new(Request                         $request,
                        EntityManagerInterface          $entityManager,
                        SandwichRepository              $sandwichRepo,
                        BoissonRepository               $boissonRepo,
                        DessertRepository               $dessertRepo,
                        DesactivationCommandeRepository $deactiveRepo,
                        LimitationCommandeRepository    $limiteRepo,
                        InscriptionCantineRepository    $cantineRepository,
                        CommandeIndividuelleRepository  $commandeRepo,
                        EleveRepository                 $eleveRepository,
                        UserRepository                  $userRepo): Response
    {
        /*R??cup??re l'utilisateur et son r??le*/
        $user = $userRepo->find($this->getUser());
        $roles = $user->getRoles();
        $cantine = null;
        $dateNow = new DateTime('now', new DateTimeZone('Europe/Paris'));

        /*R??cup??re les limites mises en place*/
        $limiteJourMeme = $limiteRepo->findOneById(1);
        $limite = new DateTime('now ' . $limiteJourMeme->getHeureLimite()->format('h:i'), new DateTimeZone('Europe/Paris'));
        $limiteNbJour = $limiteRepo->findOneById(2);
        $limiteNbSemaine = $limiteRepo->findOneById(3);
        $limiteNbMois = $limiteRepo->findOneById(4);
        $debutService = $limiteRepo->findOneById(6);
        $finService = $limiteRepo->findOneById(7);
        $nbCommandeJournalier = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 day 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeSemaine = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 week 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeMois = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 month 23:59:00', new DateTimezone('Europe/Paris'))));
        $deactive = $deactiveRepo->findOneBy(['id' => 1]);

        /*R??cup??ration des produits disponibles*/
        $sandwichs = $sandwichRepo->findByDispo(true);
        $boissons = $boissonRepo->findByDispo(true);
        $desserts = $dessertRepo->findByDispo(true);

        /*Formulaire de commande*/
        $commandeIndividuelle = new CommandeIndividuelle();
        $form = $this->createForm(CommandeIndividuelleType::class, $commandeIndividuelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandeur = $form->get('commandeur')->getData();
            $raisonPrecis = $form->get('raisonCommandeAutre')->getData();
            $dateLivraison = $form->get('dateHeureLivraison')->getData();
            $error = false;
            /*V??rifie si l'utilisateur n'est pas un administrateur ou un personnel de cuisine
             et que la commande est faite avant la cl??ture des commandes pour le jour m??me
             sinon affiche un message d'erreur
            */
            if (!in_array("ROLE_ADMIN", $roles) && !in_array("ROLE_CUISINE", $roles)) {
                if (($limiteJourMeme->getIsActive() && $limite->format('m-d-y H:i') < $dateNow->format('m-d-y H:i')) &&
                    ($dateLivraison > new DateTime('now 00:00:00',
                            new DateTimeZone('Europe/Paris')) &&
                        $dateLivraison < new DateTime('now 23:59:59',
                            new DateTimeZone('Europe/Paris')))) {
                    $this->addFlash(
                        'limiteCloture',
                        'Vous avez d??pass?? l\'heure de cl??ture pour les commandes d\'aujourd\'hui !'
                    );
                    $error = true;
                } elseif ($dateLivraison->format('l') == "Saturday" or $dateLivraison->format('l') == "Sunday") {
                    /*V??rifie que la commande n'est pas faite le samedi ou le dimanche
                     sinon un message d'erreur
                    */
                    $this->addFlash(
                        'limiteCloture',
                        'Vous ne pouvez pas faire une commande pour le samedi ou pour le dimanche !'
                    );
                    $error = true;
                }

                /*V??rifie si l'utilisateur est un ??l??ve*/
                if (in_array("ROLE_ELEVE", $roles)) {

                    /*R??cup??re l'inscription ?? la cantine de l'??l??ve
                     si l'utilisateur qui commande est un ??l??ve
                    */
                    if (in_array("ROLE_ELEVE", $roles)) {
                        $eleve = $eleveRepository->findOneByCompte($user);
                        $cantine = $cantineRepository->findOneByEleve($eleve->getId());
                    }

                    /*V??rifie si la commande est entre les heures de d??but et fin de service
                     sinon message d'erreur
                    */
                    if ($debutService->getIsActive() === true && $finService->getIsActive() === true) {
                        if (!(($dateLivraison->format('y-m-d ' . $debutService->getHeureLimite()->format('H:i')) <= $dateLivraison->format('y-m-d H:i'))
                            && ($dateLivraison->format('y-m-d H:i') < $dateLivraison->format('y-m-d ' . $finService->getHeureLimite()->format('H:i'))))) {
                            $this->addFlash(
                                'limiteCloture',
                                'Veuillez passer une commande entre ' . $debutService->getHeureLimite()->format('H:i') . ' et ' . $finService->getHeureLimite()->format('H:i') . ' !'
                            );
                            $error = true;
                        }
                    }
                    /*V??rifie si l'??l??ve qui a command?? son sandwich est inscrit
                     le jour de la livraison ?? la cantine sinon message d'erreur
                    */
                    switch ($dateLivraison->format('l')) {
                        case "Monday":
                            if (!$cantine->getRepasJ1()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le lundi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le lundi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                        case "Tuesday":
                            if (!$cantine->getRepasJ2()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le mardi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le mardi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                        case "Wednesday":
                            if (!$cantine->getRepasJ3()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le mercredi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le mercredi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                        case "Thursday":
                            if (!$cantine->getRepasJ4()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le jeudi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le jeudi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                        case "Friday":
                            if (!$cantine->getRepasJ5()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le vendredi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le vendredi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                    }
                }
                /*V??rifie si l'utilisateur n'a pas command?? deux fois le m??me jour*/
                if (!in_array("ROLE_ADMIN", $roles) && !in_array("ROLE_CUISINE", $roles)) {
                    $nbCommande = $commandeRepo->limiteCommande($user, $dateLivraison);
                    if (count($nbCommande) > 1) {
                        $this->addFlash(
                            'limiteCloture',
                            'Vous ne pouvez pas faire 2 commandes pour la m??me journ??e !'
                        );
                        $error = true;
                    }
                } else {
                    if ($commandeur) {
                        $nbCommande = $commandeRepo->limiteCommande($commandeur, $dateLivraison);
                        if (count($nbCommande) > 1) {
                            $this->addFlash(
                                'limiteCloture',
                                'Vous ne pouvez pas faire 2 commandes pour la m??me journ??e et pour la m??me personne !'
                            );
                            $error = true;
                        }
                    }
                }

                if ($form->get('raisonCommande')->getData() == "Autre" && $raisonPrecis == "Ajouter text") {
                    $this->addFlash(
                        'precisionReason',
                        'Veuillez pr??ciser votre raison !'
                    );
                    $error = true;
                }
            }

            /*Si aucune erreur est trouv??e alors la commande est envoy?? ?? la base de donn??e*/
            if (!$error) {
                /*V??rifie si la raison choisie est autre
                 et r??cup??re le champ raison pr??cis??*/
                if ($form->get('raisonCommande')->getData() == "Autre") {
                    $commandeIndividuelle->setRaisonCommande($raisonPrecis);
                } else {
                    $commandeIndividuelle->setRaisonCommande($form->get('raisonCommande')->getData());
                }
                /*V??rifie si le champ commande est rempli
                 et met le commandeur dans la base de donn??e*/
                if ($form->get('commandeur')->getData() != null) {
                    $commandeIndividuelle->setCommandeur($form->get('commandeur')->getData());
                } else {
                    $commandeIndividuelle->setCommandeur($user);
                }

                $commandeIndividuelle
                    ->setDateCreation($dateNow)
                    ->setEstValide(true);
                $entityManager->persist($commandeIndividuelle);
                $entityManager->flush();

                $this->addFlash(
                    'SuccessComInd',
                    'Votre commande a ??t?? sauvegard??e !'
                );
            }
            return $this->redirectToRoute('commande_individuelle_new', [], Response::HTTP_SEE_OTHER);
        }


        if ($deactive->getIsDeactivated() === true) {
            return $this->redirectToRoute('deactivate_commande');
        } else {
            return $this->render('commande_individuelle/new.html.twig', [
                'commande_individuelle' => $commandeIndividuelle,
                'form' => $form->createView(),
                'sandwichs' => $sandwichs,
                'boissons' => $boissons,
                'desserts' => $desserts,
                'limiteJourMeme' => $dateNow->format('d-m-y H:i'),
                'limiteNbJournalier' => $limiteNbJour->getNbLimite(),
                'limiteActiveNbJour' => $limiteNbJour->getIsActive(),
                'limiteNbSemaine' => $limiteNbSemaine->getNbLimite(),
                'limiteActiveNbSemaine' => $limiteNbSemaine->getIsActive(),
                'limiteNbMois' => $limiteNbMois->getNbLimite(),
                'limiteActiveNbMois' => $limiteNbMois->getIsActive(),
                'nbCommandeJournalier' => $nbCommandeJournalier,
                'nbCommandeSemaine' => $nbCommandeSemaine,
                'nbCommandeMois' => $nbCommandeMois,
            ]);
        }
    }

    /**
     * Affichage de la page de d??sactivation de service
     * @Route("/desactive", name="deactivate_commande",methods={"GET"})
     */
    public function deactivated(): Response
    {
        return $this->render(
            'commande_individuelle/deactive.html.twig'
        );
    }

    /**
     * Formulaire permettant de valider ou invalider la commande
     * @Route("/validate/{id}", name="validate_commande",methods={"GET","POST"})
     * @param CommandeIndividuelle $commande
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function validateCommande(CommandeIndividuelle $commande, EntityManagerInterface $entityManager): RedirectResponse
    {
        /*V??rifie si la commande est valide ou pas et change son contraire*/
        if ($commande->getEstValide() === false) {
            $commande->setEstValide(true);
        } else {
            $commande->setEstValide(false);
        }
        $entityManager->persist($commande);
        $entityManager->flush();

        return $this->redirectToRoute('commande_individuelle_admin', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Page de pr??-suppression d'une commande
     * @Route("/{id}/delete_view", name="commande_individuelle_delete_view", methods={"GET","POST"})
     * @param CommandeIndividuelle $commandeIndividuelle
     * @return Response
     */
    public function delete_view(CommandeIndividuelle $commandeIndividuelle): Response
    {
        return $this->render('commande_individuelle/delete_view.html.twig', [
            'commande' => $commandeIndividuelle,
        ]);
    }

    /**
     * Formulaire de modification d'une commande individuelle
     * @Route("/{id}/edit", name="commande_individuelle_edit", methods={"GET", "POST"})
     * @param Request $request
     * @param CommandeIndividuelle $commandeIndividuelle
     * @param EntityManagerInterface $entityManager
     * @param SandwichRepository $sandwichRepo
     * @param BoissonRepository $boissonRepo
     * @param DessertRepository $dessertRepo
     * @param DesactivationCommandeRepository $deactiveRepo
     * @param LimitationCommandeRepository $limiteRepo
     * @param InscriptionCantineRepository $cantineRepository
     * @param EleveRepository $eleveRepository
     * @param CommandeIndividuelleRepository $commandeRepo
     * @param UserRepository $userRepo
     * @return Response
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function edit(Request                         $request,
                         CommandeIndividuelle            $commandeIndividuelle,
                         EntityManagerInterface          $entityManager,
                         SandwichRepository              $sandwichRepo,
                         BoissonRepository               $boissonRepo,
                         DessertRepository               $dessertRepo,
                         DesactivationCommandeRepository $deactiveRepo,
                         LimitationCommandeRepository    $limiteRepo,
                         InscriptionCantineRepository    $cantineRepository,
                         EleveRepository                 $eleveRepository,
                         CommandeIndividuelleRepository  $commandeRepo,
                         UserRepository                  $userRepo): Response
    {
        /*R??cup??re l'utilisateur courant et son r??le*/
        $user = $userRepo->find($this->getUser());
        $roles = $user->getRoles();

        /*R??cup??ration des limites mises en place*/
        $limiteJourMeme = $limiteRepo->findOneById(1);
        $limite = new DateTime('now ' . $limiteJourMeme->getHeureLimite()->format('h:i'), new DateTimeZone('Europe/Paris'));
        $dateNow = new DateTime('now', new DateTimeZone('Europe/Paris'));
        $limiteNbJour = $limiteRepo->findOneById(2);
        $limiteNbSemaine = $limiteRepo->findOneById(3);
        $limiteNbMois = $limiteRepo->findOneById(4);
        $debutService = $limiteRepo->findOneById(6);
        $finService = $limiteRepo->findOneById(7);
        $nbCommandeJournalier = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 day 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeSemaine = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 week 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeMois = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 month 23:59:00', new DateTimezone('Europe/Paris'))));

        /*R??cup??ration des produits et de la donn??e qui d??sactive le service de commandes*/
        $deactive = $deactiveRepo->findOneBy(['id' => 1]);
        $sandwichs = $sandwichRepo->findByDispo(true);
        $boissons = $boissonRepo->findByDispo(true);
        $desserts = $dessertRepo->findByDispo(true);

        $form = $this->createForm(CommandeIndividuelleType::class, $commandeIndividuelle);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $commandeur = $form->get('commandeur')->getData();
            $raisonPrecis = $form->get('raisonCommandeAutre')->getData();
            $dateLivraison = $form->get('dateHeureLivraison')->getData();
            $error = false;
            /*V??rifie si l'utilisateur n'est pas un administrateur ou un personnel de cuisine
             et que la commande est faite avant la cl??ture des commandes pour le jour m??me
             sinon affiche un message d'erreur
            */
            if (!in_array("ROLE_ADMIN", $roles) && !in_array("ROLE_CUISINE", $roles)) {
                if (($limiteJourMeme->getIsActive() && $limite->format('m-d-y H:i') < $dateNow->format('m-d-y H:i')) &&
                    ($dateLivraison > new DateTime('now 00:00:00',
                            new DateTimeZone('Europe/Paris')) &&
                        $dateLivraison < new DateTime('now 23:59:59',
                            new DateTimeZone('Europe/Paris')))) {
                    $this->addFlash(
                        'limiteCloture',
                        'Vous avez d??pass?? l\'heure de cl??ture pour les commandes d\'aujourd\'hui !'
                    );
                    $error = true;
                } elseif ($dateLivraison->format('l') == "Saturday" or $dateLivraison->format('l') == "Sunday") {
                    /*V??rifie que la commande n'est pas faite le samedi ou le dimanche
                     sinon un message d'erreur
                    */
                    $this->addFlash(
                        'limiteCloture',
                        'Vous ne pouvez pas faire une commande pour le samedi ou pour le dimanche !'
                    );
                    $error = true;
                }

                /*V??rifie si l'utilisateur est un ??l??ve*/
                if (in_array("ROLE_ELEVE", $roles)) {
                    /*R??cup??re les inscriptions ?? la cantine de l'??l??ve*/
                    $cantine = $cantineRepository->findOneByEleve($user->getEleves()->first()->getId());
                    /*V??rifie si la commande est entre les heures de d??but et fin de service
                     sinon message d'erreur
                    */
                    if ($debutService->getIsActive() === true && $finService->getIsActive() === true) {
                        if (!(($dateLivraison->format('y-m-d ' . $debutService->getHeureLimite()->format('H:i')) <= $dateLivraison->format('y-m-d H:i'))
                            && ($dateLivraison->format('y-m-d H:i') < $dateLivraison->format('y-m-d ' . $finService->getHeureLimite()->format('H:i'))))) {
                            $this->addFlash(
                                'limiteCloture',
                                'Veuillez passer une commande entre ' . $debutService->getHeureLimite()->format('H:i') . ' et ' . $finService->getHeureLimite()->format('H:i') . ' !'
                            );
                            $error = true;
                        }
                    }
                    /*V??rifie si l'??l??ve qui a command?? son sandwich est inscrit
                     le jour de la livraison ?? la cantine sinon message d'erreur
                    */
                    switch ($dateLivraison->format('l')) {
                        case "Monday":
                            if (!$cantine->getRepasJ1()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le lundi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le lundi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                        case "Tuesday":
                            if (!$cantine->getRepasJ2()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le mardi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le mardi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                        case "Wednesday":
                            if (!$cantine->getRepasJ3()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le mercredi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le mercredi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                        case "Thursday":
                            if (!$cantine->getRepasJ4()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le jeudi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le jeudi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                        case "Friday":
                            if (!$cantine->getRepasJ5()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'??tes pas inscrit(e) le vendredi ?? la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'??l??ve n\'est pas inscrit(e) le vendredi ?? la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                    }
                }
                /*V??rifie si l'utilisateur n'a pas command?? deux fois le m??me jour*/
                if (!in_array("ROLE_ADMIN", $roles) && !in_array("ROLE_CUISINE", $roles)) {
                    $nbCommande = $commandeRepo->limiteCommande($user, $dateLivraison);
                    if (count($nbCommande) > 1) {
                        $this->addFlash(
                            'limiteCloture',
                            'Vous ne pouvez pas faire 2 commandes pour la m??me journ??e !'
                        );
                        $error = true;
                    }
                    if ($form->get('raisonCommande')->getData() == "Autre" && $raisonPrecis == "Ajouter text") {
                        $this->addFlash(
                            'precisionReason',
                            'Veuillez pr??ciser votre raison !'
                        );
                        $error = true;
                    }
                } else {
                    if ($commandeur) {
                        $nbCommande = $commandeRepo->limiteCommande($commandeur, $dateLivraison);
                        if (count($nbCommande) > 1) {
                            $this->addFlash(
                                'limiteCloture',
                                'Vous ne pouvez pas faire 2 commandes pour la m??me journ??e et pour la m??me personne !'
                            );
                            $error = true;
                        }
                    }
                }
            }

            /*Si aucune erreur est trouv??e alors la commande est envoy?? ?? la base de donn??e*/
            if (!$error) {
                /*V??rifie si la raison choisie est autre
                 et r??cup??re le champ raison pr??cis??*/
                if ($form->get('raisonCommande')->getData() == "Autre") {
                    $commandeIndividuelle->setRaisonCommande($raisonPrecis);
                } else {
                    $commandeIndividuelle->setRaisonCommande($form->get('raisonCommande')->getData());
                }
                /*V??rifie si le champ commande est rempli
                 et met le commandeur dans la base de donn??e*/
                if ($commandeur) {
                    $commandeIndividuelle->setCommandeur($form->get('commandeur')->getData());
                } else {
                    $commandeIndividuelle->setCommandeur($commandeIndividuelle->getCommandeur());
                }

                $entityManager->flush();

                $this->addFlash(
                    'SuccessComInd',
                    'Votre commande a ??t?? sauvegard??e !'
                );
            }

            return $this->redirectToRoute('commande_individuelle_edit',
                ['id' => $commandeIndividuelle->getId()], Response::HTTP_SEE_OTHER);
        }

        /*Si le service est d??activ?? alors retourne la page de d??sactivation de service*/
        if ($deactive->getIsDeactivated() === true) {
            return $this->redirectToRoute('deactivate_commande');
        } else {
            /*Sinon retourne le formulaire de modification de commande individuelle*/
            return $this->render('commande_individuelle/edit.html.twig', [
                'commande_individuelle' => $commandeIndividuelle,
                'form' => $form->createView(),
                'sandwichs' => $sandwichs,
                'boissons' => $boissons,
                'desserts' => $desserts,
                'limiteJourMeme' => $dateNow->format('d-m-y H:i'),
                'limiteNbJournalier' => $limiteNbJour->getNbLimite(),
                'limiteActiveNbJour' => $limiteNbJour->getIsActive(),
                'limiteNbSemaine' => $limiteNbSemaine->getNbLimite(),
                'limiteActiveNbSemaine' => $limiteNbSemaine->getIsActive(),
                'limiteNbMois' => $limiteNbMois->getNbLimite(),
                'limiteActiveNbMois' => $limiteNbMois->getIsActive(),
                'nbCommandeJournalier' => $nbCommandeJournalier,
                'nbCommandeSemaine' => $nbCommandeSemaine,
                'nbCommandeMois' => $nbCommandeMois,
            ]);
        }
    }

    /**
     * Formulaire de suppression d'une commande
     * @Route("/{id}", name="commande_individuelle_delete", methods={"POST"})
     * @param Request $request
     * @param CommandeIndividuelle $commandeIndividuelle
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(Request                $request,
                           CommandeIndividuelle   $commandeIndividuelle,
                           EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commandeIndividuelle->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commandeIndividuelle);
            $entityManager->flush();

            /*Message de validation*/
            $this->addFlash(
                'SuccessDeleteComInd',
                'La commande a ??t?? annul??e !'
            );
        }

        if (in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('commande_individuelle_admin', [], Response::HTTP_SEE_OTHER);
        } else {
            return $this->redirectToRoute('commande_individuelle_index', [], Response::HTTP_SEE_OTHER);
        }
    }
}
