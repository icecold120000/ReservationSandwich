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
     * @param int $comIndPage Utilisé pour le filtre et la pagination des commandes individuelles
     * @param int $comGrPage Utilisé pour le filtre et la pagination des commandes groupées
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
        /*Récupère l'utilisateur courant*/
        $user = $userRepo->find($this->getUser());

        /*Affichage par défault de la page*/
        $affichageTableau = "les deux";

        /*Récupèration des limites mise en place sur les commandes*/
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

        /*Récupère les commandes de l'utilisateur*/
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

        /*Pagination des commandes groupées*/
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
     * Formulaire permettant de désactiver et réactiver le service de commande
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
                'Les pages de réservations ont été désactivées !'
            );
        } elseif ($desactiveId->getIsDeactivated() === true) {
            $desactiveId->setIsDeactivated(false);
            $this->addFlash(
                'SuccessDeactivation',
                'Les pages de réservations ont été réactivées !'
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
     * @param int $comIndPage Utilisé pour le filtre et la pagination des commandes individuelles
     * @param int $comGrPage Utilisé pour le filtre et la pagination des commandes groupées
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
        /*Récupération des commandes + affichage par défault de la page*/
        $affichageTableau = "les deux";
        $commandes = $comIndRepo->findAllNonCloture();
        $commandesGroupe = $comGrRepo->findAllAdminNonClotureGroupe();

        /*Formulaire d'exportation de commandes*/
        $export = $this->createForm(FilterExportationType::class);
        $exportReq = $export->handleRequest($request);

        if ($export->isSubmitted() && $export->isValid()) {
            /*Récupération et formatage de la date choisi par l'utilisateur*/
            $dateChoisi = $exportReq->get('dateExport')->getData();
            $dateChoisi = $dateChoisi->format('y-m-d');

            /*Récupération de l'affichage du rendu*/
            $modalite = $exportReq->get('modaliteCommande')->getData();

            /*Récupération des commandes et commandes groupées selon l'affichage de l'export*/
            if ($exportReq->get('affichageExport')->getData() == "les deux" ||
                $exportReq->get('affichageExport')->getData() == "individuelle") {
                $commandesExport = $comIndRepo->exportationCommande($dateChoisi);
            } else {
                $commandesExport = null;
            }
            if ($exportReq->get('affichageExport')->getData() == "les deux" ||
                $exportReq->get('affichageExport')->getData() == "groupé") {
                $commandesGroupeExport = $comGrRepo->exportationCommandeGroupe($dateChoisi);
            } else {
                $commandesGroupeExport = null;
            }

            /*Récupération du type rendu attendu par l'utilisateur (PDF, Excel ou Impression)*/
            $methode = $exportReq->get('methodeExport')->getData();
            if ($methode == "PDF") {
                /*Fonction permettant de mettre en pdf les commandes*/
                CommandeIndividuelleController::pdfDownload($commandesExport, $commandesGroupeExport, $modalite, $exportReq->get('dateExport')->getData());
            } elseif ($methode == "Excel") {
                /*Vérifie si le rendu attendu est les commandes sont affichées un par un*/
                if ($modalite == "Séparées") {
                    $commandeRow = [];
                    $commandeGroupeRow = [];
                    /*Vérifie s'il y a des commandes individuelles a exporté*/
                    if ($commandesExport) {
                        /*Pour chaque commande*/
                        foreach ($commandesExport as $commande) {
                            /*Vérifie si la commande est faite par un élève et récupère la classe de l'élève
                             Sinon les adultes sont attribués une classe adulte
                            */
                            if (in_array(User::ROLE_ELEVE, $commande->getCommandeur()->getRoles())) {
                                $eleve = $this->eleveRepo->findOneByCompte($commande->getCommandeur());
                                $classe = $eleve->getClasseEleve()->getCodeClasse();
                            } else {
                                $classe = "Adulte";
                            }
                            /*Récupère si la commande a des chips commandés*/
                            if ($commande->getPrendreChips()) {
                                $chips = "Oui";
                            } else {
                                $chips = "Non";
                            }
                            /*Place les données dans une ligne*/
                            $commandeRow[] = [
                                'Date et heure de Livraison' => $commande->getDateHeureLivraison()->format('d/m/y h:i'),
                                'Prénom et Nom' => $commande->getCommandeur()->getPrenomUser() . ' ' . $commande->getCommandeur()->getNomUser(),
                                'Classe' => $classe,
                                'Commande' => $commande->getSandwichChoisi()->getNomSandwich() . ', ' . $commande->getBoissonChoisie()->getNomBoisson() . ', ' . $commande->getDessertChoisi()->getNomDessert(),
                                'Chips' => $chips,
                            ];
                        }
                    }
                    if ($commandesGroupeExport) {
                        foreach ($commandesGroupeExport as $commandeGroupe) {
                            /*Vérifie si la commande est faite par un élève et récupère la classe de l'élève
                             Sinon les adultes sont attribués une classe adulte
                            */
                            if (in_array(User::ROLE_ELEVE, $commandeGroupe->getCommandeur()->getRoles())) {
                                $eleve = $this->eleveRepo->findOneByCompte($commandeGroupe->getCommandeur());
                                $classe = $eleve->getClasseEleve()->getCodeClasse();
                            } else {
                                $classe = "Adulte";
                            }

                            $sandwichsGroupe = [];
                            $nombreEleve = 0;
                            /*Récupère les sandwichs commandés*/
                            foreach ($commandeGroupe->getSandwichCommandeGroupes() as $sandwichChoisi) {
                                $nombreEleve = $nombreEleve + $sandwichChoisi->getNombreSandwich();
                                $sandwichsGroupe[] = $sandwichChoisi->getNombreSandwich() . ' ' . $sandwichChoisi->getSandwichChoisi()->getNomSandwich();
                            }
                            /*Place les données dans une ligne*/
                            $commandeGroupeRow[] = [
                                'Date et heure de Livraison' => $commandeGroupe->getDateHeureLivraison()->format('d/m/y h:i'),
                                'Prénom et Nom' => $commandeGroupe->getCommandeur()->getPrenomUser() . ' ' . $commandeGroupe->getCommandeur()->getNomUser(),
                                'Classe' => $classe,
                                'Commande' => $sandwichsGroupe[0] . ', ' . $sandwichsGroupe[1] . ', ' . $nombreEleve . ' ' . $commandeGroupe->getBoissonChoisie()->getNomBoisson() . ', ' . $nombreEleve . ' ' . $commandeGroupe->getDessertChoisi()->getNomDessert(),
                                'Chips' => $nombreEleve . ' Chips',
                            ];
                        }
                    }

                    $encoder = new ExcelEncoder([]);
                    /*Vérifie s'il y a des commandes et des commandes groupées*/
                    if ($commandeGroupeRow != [] && $commandeRow != []) {
                        /*Regroupe les commandes et commandes groupées dans un même
                         tableau et le met dans un tableau qui sera dans une feuille excel
                        */
                        $commandesRegroupe = array_merge($commandeRow, $commandeGroupeRow);
                        $data = [
                            // Array by sheet
                            'Feuille 1' => $commandesRegroupe
                        ];
                    } else {
                        /*Met les commandes (groupées) dans un tableau qui sera dans une feuille excel*/
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
                    file_put_contents('Commandes_Séparées_' . $dateChoisi->format('d-m-y') . '.xlsx', $xls);
                    $filename = 'Commandes_Séparées_' . $dateChoisi->format('d-m-y') . '.xlsx';
                    //Permet le téléchargement du fichier
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header("Cache-Control: no-cache, must-revalidate");
                    header("Expires: 0");
                    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                    header('Content-Length: ' . filesize($filename));
                    header('Pragma: public');

                    readfile($filename);
                    // Déplace le fichier dans le dossier Uploads
                    rename($filename, $this->getParameter('exportFile_directory') . $filename);

                } elseif ($modalite == "Regroupées") {
                    /*Récupère les produits disponibles*/
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
                    /*Compte le nombre de sandwichs commandés pour chaque sandwich*/
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
                    /*Compte le nombre de boissons commandés pour chaque boisson*/
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
                            /*Récupère le nombre de chips pour les commandes groupées*/
                            $nbChips = $nbChips + $nombreEleve;
                        }
                        if ($commandesExport != null) {
                            $nbDessert[] = count($comIndRepo->findByDessert($dessert->getId(), $dateChoisi)) + $nombreEleve;
                        }
                    }

                    /*Place pour chaque sandwich, le nombre total de sandwich commandé*/
                    $dataRowSandwich = [];
                    for ($i = 0; $i < count($nomSandwich); $i++) {
                        $dataRowSandwich[$i] = [
                            'Nom de produit' => $nomSandwich[$i],
                            'Nombre de produit' => $nbSandwich[$i],
                        ];
                    }

                    /*Place pour chaque boisson, le nombre total de boisson commandée*/
                    $dataRowBoisson = [];
                    for ($i = 0; $i < count($nomBoisson); $i++) {
                        $dataRowBoisson[$i] = [
                            'Nom de produit' => $nomBoisson[$i],
                            'Nombre de produit' => $nbBoisson[$i],
                        ];
                    }

                    /*Place pour chaque dessert, le nombre total de dessert commandé*/
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
                    /*Place les données dans un tableau*/
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
                    /* Place les données dans une feuille*/
                    $data = [
                        // Array by sheet
                        'Feuille 1' => $dataRow
                    ];

                    // Encode data with specific format
                    $xls = $encoder->encode($data, ExcelEncoder::XLSX);
                    $dateChoisi = $exportReq->get('dateExport')->getData();

                    // Put the content in a file with format extension for example
                    file_put_contents('Commandes_Regroupées_' . $dateChoisi->format('d-m-y') . '.xlsx', $xls);
                    $filename = 'Commandes_Regroupées_' . $dateChoisi->format('d-m-y') . '.xlsx';
                    /*Permet le téléchargement du fichier*/
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header("Cache-Control: no-cache, must-revalidate");
                    header("Expires: 0");
                    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                    header('Content-Length: ' . filesize($filename));
                    header('Pragma: public');
                    readfile($filename);
                    /*Déplace le fichier dans le dossier Uploads*/
                    rename($filename, $this->getParameter('excelFile_directory') . $filename);
                }
                return new Response();
            } elseif ($methode == "Impression") {
                /*Ouvrir la page de pré-impression avec les données*/
                return CommandeIndividuelleController::printPreview($modalite,
                    $exportReq->get('dateExport')->getData(),
                    $exportReq->get('affichageExport')->getData());
            }

        } elseif ($export->isSubmitted()) {
            $this->addFlash(
                'failedExport',
                'Votre export a échoué à la suite d\'une erreur !'
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

        /*Pagination des commandes groupées*/
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
        // Défini les options du pdf
        $optionsPdf = new OptionsPdf();

        // Donne une police par défaut
        $optionsPdf->set('defaultFont', 'Arial');
        $optionsPdf->setIsRemoteEnabled(true);

        // Instancie Dompdf
        $dompdf = new Dompdf($optionsPdf);
        // Créer le context http du pdf
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        // Donne le context http au pdf
        $dompdf->setHttpContext($context);

        // Génère le pdf et le rendu html à partir du TWIG selon type de rendu choisi
        if ($modalite == "Séparées") {
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

        // Génère l'affichage du pdf dans un onglet
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        $date = $dateChoisi->format('d-m-Y');

        // Nomme le fichier PDF
        $fichier = 'Commandes_' . $modalite . '_' . $date . '.pdf';

        // Télécharge le pdf
        $dompdf->stream($fichier, [
            'Attachment' => true
        ]);

        // Retourne le résultat
        return new Response();
    }

    /**
     * Page de pré-impression de l'exportation des commandes
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
            /*Si oui, alors il y a récupèration des commandes individuelles*/
            $commandes = $this->comIndRepo
                ->exportationCommande($dateChoisi->format('y-m-d'));
        } else {
            /*Sinon les commandes individuelles sont rendu null*/
            $commandes = null;
        }

        /*Si l'affichage choisi est groupées ou les deux*/
        if ($affichage == "les deux" || $affichage == "groupées") {
            /*Si oui, alors il y a récupèration des commandes groupées*/
            $commandeGroupe = $this->comGrRepo->exportationCommandeGroupe($dateChoisi->format('y-m-d'));
        } else {
            /*Sinon les commandes groupées sont rendu null*/
            $commandeGroupe = null;
        }

        /*Affichage du rendu selon ce qui est demandé*/
        if ($modalite == "Séparées") {
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
        /*Récupère l'utilisateur et son rôle*/
        $user = $userRepo->find($this->getUser());
        $roles = $user->getRoles();
        $cantine = null;
        $dateNow = new DateTime('now', new DateTimeZone('Europe/Paris'));

        /*Récupère les limites mises en place*/
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

        /*Récupèration des produits disponibles*/
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
            /*Vérifie si l'utilisateur n'est pas un administrateur ou un personnel de cuisine
             et que la commande est faite avant la clôture des commandes pour le jour même
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
                        'Vous avez dépassé l\'heure de clôture pour les commandes d\'aujourd\'hui !'
                    );
                    $error = true;
                } elseif ($dateLivraison->format('l') == "Saturday" or $dateLivraison->format('l') == "Sunday") {
                    /*Vérifie que la commande n'est pas faite le samedi ou le dimanche
                     sinon un message d'erreur
                    */
                    $this->addFlash(
                        'limiteCloture',
                        'Vous ne pouvez pas faire une commande pour le samedi ou pour le dimanche !'
                    );
                    $error = true;
                }

                /*Vérifie si l'utilisateur est un élève*/
                if (in_array("ROLE_ELEVE", $roles)) {

                    /*Récupère l'inscription à la cantine de l'élève
                     si l'utilisateur qui commande est un élève
                    */
                    if (in_array("ROLE_ELEVE", $roles)) {
                        $eleve = $eleveRepository->findOneByCompte($user);
                        $cantine = $cantineRepository->findOneByEleve($eleve->getId());
                    }

                    /*Vérifie si la commande est entre les heures de début et fin de service
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
                    /*Vérifie si l'élève qui a commandé son sandwich est inscrit
                     le jour de la livraison à la cantine sinon message d'erreur
                    */
                    switch ($dateLivraison->format('l')) {
                        case "Monday":
                            if (!$cantine->getRepasJ1()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'êtes pas inscrit(e) le lundi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le lundi à la cantine !'
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
                                        'Vous n\'êtes pas inscrit(e) le mardi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le mardi à la cantine !'
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
                                        'Vous n\'êtes pas inscrit(e) le mercredi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le mercredi à la cantine !'
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
                                        'Vous n\'êtes pas inscrit(e) le jeudi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le jeudi à la cantine !'
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
                                        'Vous n\'êtes pas inscrit(e) le vendredi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le vendredi à la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                    }
                }
                /*Vérifie si l'utilisateur n'a pas commandé deux fois le même jour*/
                if (!in_array("ROLE_ADMIN", $roles) && !in_array("ROLE_CUISINE", $roles)) {
                    $nbCommande = $commandeRepo->limiteCommande($user, $dateLivraison);
                    if (count($nbCommande) > 1) {
                        $this->addFlash(
                            'limiteCloture',
                            'Vous ne pouvez pas faire 2 commandes pour la même journée !'
                        );
                        $error = true;
                    }
                } else {
                    if ($commandeur) {
                        $nbCommande = $commandeRepo->limiteCommande($commandeur, $dateLivraison);
                        if (count($nbCommande) > 1) {
                            $this->addFlash(
                                'limiteCloture',
                                'Vous ne pouvez pas faire 2 commandes pour la même journée et pour la même personne !'
                            );
                            $error = true;
                        }
                    }
                }

                if ($form->get('raisonCommande')->getData() == "Autre" && $raisonPrecis == "Ajouter text") {
                    $this->addFlash(
                        'precisionReason',
                        'Veuillez préciser votre raison !'
                    );
                    $error = true;
                }
            }

            /*Si aucune erreur est trouvée alors la commande est envoyé à la base de donnée*/
            if (!$error) {
                /*Vérifie si la raison choisie est autre
                 et récupère le champ raison précisé*/
                if ($form->get('raisonCommande')->getData() == "Autre") {
                    $commandeIndividuelle->setRaisonCommande($raisonPrecis);
                } else {
                    $commandeIndividuelle->setRaisonCommande($form->get('raisonCommande')->getData());
                }
                /*Vérifie si le champ commande est rempli
                 et met le comandeur dans la base de donnée*/
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
                    'Votre commande a été sauvegardée !'
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
     * Affichage de la page de déactivation de service
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
        /*Vérifie si la commande est valide ou pas et change son contraire*/
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
     * Page de pré-suppression d'une commande
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
        /*Récupère l'utilisateur courant et son rôle*/
        $user = $userRepo->find($this->getUser());
        $roles = $user->getRoles();

        /*Récupération des limites mises en place*/
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

        /*Récupération des produits et de la donnée qui désactive le service de commandes*/
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
            /*Vérifie si l'utilisateur n'est pas un administrateur ou un personnel de cuisine
             et que la commande est faite avant la clôture des commandes pour le jour même
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
                        'Vous avez dépassé l\'heure de clôture pour les commandes d\'aujourd\'hui !'
                    );
                    $error = true;
                } elseif ($dateLivraison->format('l') == "Saturday" or $dateLivraison->format('l') == "Sunday") {
                    /*Vérifie que la commande n'est pas faite le samedi ou le dimanche
                     sinon un message d'erreur
                    */
                    $this->addFlash(
                        'limiteCloture',
                        'Vous ne pouvez pas faire une commande pour le samedi ou pour le dimanche !'
                    );
                    $error = true;
                }

                /*Vérifie si l'utilisateur est un élève*/
                if (in_array("ROLE_ELEVE", $roles)) {
                    /*Récupère les inscriptions à la cantine de l'élève*/
                    $cantine = $cantineRepository->findOneByEleve($user->getEleves()->first()->getId());
                    /*Vérifie si la commande est entre les heures de début et fin de service
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
                    /*Vérifie si l'élève qui a commandé son sandwich est incrit
                     le jour de la livraison à la cantine sinon message d'erreur
                    */
                    switch ($dateLivraison->format('l')) {
                        case "Monday":
                            if (!$cantine->getRepasJ1()) {
                                if ($commandeur === null) {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'Vous n\'êtes pas inscrit(e) le lundi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le lundi à la cantine !'
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
                                        'Vous n\'êtes pas inscrit(e) le mardi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le mardi à la cantine !'
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
                                        'Vous n\'êtes pas inscrit(e) le mercredi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le mercredi à la cantine !'
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
                                        'Vous n\'êtes pas inscrit(e) le jeudi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le jeudi à la cantine !'
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
                                        'Vous n\'êtes pas inscrit(e) le vendredi à la cantine !'
                                    );
                                } else {
                                    $this->addFlash(
                                        'limiteCloture',
                                        'L\'élève n\'est pas inscrit(e) le vendredi à la cantine !'
                                    );
                                }
                                $error = true;
                            }
                            break;
                    }
                }
                /*Vérifie si l'utilisateur n'a pas commandé deux fois le même jour*/
                if (!in_array("ROLE_ADMIN", $roles) && !in_array("ROLE_CUISINE", $roles)) {
                    $nbCommande = $commandeRepo->limiteCommande($user, $dateLivraison);
                    if (count($nbCommande) > 1) {
                        $this->addFlash(
                            'limiteCloture',
                            'Vous ne pouvez pas faire 2 commandes pour la même journée !'
                        );
                        $error = true;
                    }
                    if ($form->get('raisonCommande')->getData() == "Autre" && $raisonPrecis == "Ajouter text") {
                        $this->addFlash(
                            'precisionReason',
                            'Veuillez préciser votre raison !'
                        );
                        $error = true;
                    }
                } else {
                    if ($commandeur) {
                        $nbCommande = $commandeRepo->limiteCommande($commandeur, $dateLivraison);
                        if (count($nbCommande) > 1) {
                            $this->addFlash(
                                'limiteCloture',
                                'Vous ne pouvez pas faire 2 commandes pour la même journée et pour la même personne !'
                            );
                            $error = true;
                        }
                    }
                }
            }

            /*Si aucune erreur est trouvée alors la commande est envoyé à la base de donnée*/
            if (!$error) {
                /*Vérifie si la raison choisie est autre
                 et récupère le champ raison précisé*/
                if ($form->get('raisonCommande')->getData() == "Autre") {
                    $commandeIndividuelle->setRaisonCommande($raisonPrecis);
                } else {
                    $commandeIndividuelle->setRaisonCommande($form->get('raisonCommande')->getData());
                }
                /*Vérifie si le champ commande est rempli
                 et met le comandeur dans la base de donnée*/
                if ($commandeur) {
                    $commandeIndividuelle->setCommandeur($form->get('commandeur')->getData());
                } else {
                    $commandeIndividuelle->setCommandeur($commandeIndividuelle->getCommandeur());
                }

                $entityManager->flush();

                $this->addFlash(
                    'SuccessComInd',
                    'Votre commande a été sauvegardée !'
                );
            }

            return $this->redirectToRoute('commande_individuelle_edit',
                ['id' => $commandeIndividuelle->getId()], Response::HTTP_SEE_OTHER);
        }

        /*Si le service est déactivé alors retourne la page de désactivation de service*/
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
                'La commande a été annulée !'
            );
        }

        if (in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('commande_individuelle_admin', [], Response::HTTP_SEE_OTHER);
        } else {
            return $this->redirectToRoute('commande_individuelle_index', [], Response::HTTP_SEE_OTHER);
        }
    }
}
