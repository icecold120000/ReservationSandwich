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
     * @Route("/", name="commande_individuelle_index", methods={"GET","POST"})
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function index(CommandeIndividuelleRepository $comIndRepo,
                          PaginatorInterface             $paginator,
                          Request                        $request,
                          LimitationCommandeRepository   $limiteRepo,
                          CommandeGroupeRepository       $comGrRepo,
                          UserRepository                 $userRepo): Response
    {
        $user = $userRepo->find($this->getUser());
        $affichageTableau = "les deux";
        $limiteGroupeCom = $limiteRepo->findOneById(5);
        $limiteJourMeme = $limiteRepo->findOneById(1);
        $limiteNbJour = $limiteRepo->findOneById(2);
        $limiteNbSemaine = $limiteRepo->findOneById(3);
        $limiteNbMois = $limiteRepo->findOneById(4);
        $nbCommandeJournalier = count($comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 day 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeSemaine = count($comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 week 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeMois = count($comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 month 23:59:00', new DateTimezone('Europe/Paris'))));
        $limiteDate = new DateTime('now ' . $limiteJourMeme->getHeureLimite()->format('h:i'),
            new DateTimeZone('Europe/Paris'));
        $commandes = $comIndRepo->findIndexAllNonCloture($user);
        $commandesGroupe = $comGrRepo->findAllIndexNonClotureGroupe($user);

        $form = $this->createForm(FilterIndexCommandeType::class);
        $filter = $form->handleRequest($request);
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

        $commandes = $paginator->paginate(
            $commandes,
            $request->query->getInt('page', 1),
            25
        );

        $commandesGroupe = $paginator->paginate(
            $commandesGroupe,
            $request->query->getInt('page', 1),
            5
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
     * @Route("/desactivation/{desactiveId}", name="commande_ind_desactive", methods={"GET","POST"})
     * @Entity("desactivationCommande", expr="repository.find(desactiveId)")
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
     * @Route("/admin", name="commande_individuelle_admin", methods={"GET","POST"})
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function admin(CommandeIndividuelleRepository $comIndRepo,
                          PaginatorInterface             $paginator,
                          Request                        $request,
                          CommandeGroupeRepository       $comGrRepo): Response
    {
        $affichageTableau = "les deux";
        $commandes = $comIndRepo->findAllNonCloture();
        $commandesGroupe = $comGrRepo->findAllAdminNonClotureGroupe();

        $export = $this->createForm(FilterExportationType::class);
        $exportReq = $export->handleRequest($request);

        if ($export->isSubmitted() && $export->isValid()) {
            $dateChoisi = $exportReq->get('dateExport')->getData();
            $dateChoisi = $dateChoisi->format('y-m-d');
            $modalite = $exportReq->get('modaliteCommande')->getData();
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

            $methode = $exportReq->get('methodeExport')->getData();
            if ($methode == "PDF") {
                CommandeIndividuelleController::pdfDownload($commandesExport, $commandesGroupeExport, $modalite, $exportReq->get('dateExport')->getData());
            } elseif ($methode == "Excel") {
                if ($modalite == "Séparé") {
                    $commandeRow = [];
                    $commandeGroupeRow = [];
                    if ($commandesExport) {
                        foreach ($commandesExport as $commande) {
                            if (in_array(User::ROLE_ELEVE, $commande->getCommandeur()->getRoles())) {
                                $eleve = $this->eleveRepo->findOneByCompte($commande->getCommandeur());
                                $classe = $eleve->getClasseEleve()->getCodeClasse();
                            } else {
                                $classe = "Adulte";
                            }

                            if ($commande->getPrendreChips()) {
                                $chips = "Oui";
                            } else {
                                $chips = "Non";
                            }
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
                            if (in_array(User::ROLE_ELEVE, $commandeGroupe->getCommandeur()->getRoles())) {
                                $eleve = $this->eleveRepo->findOneByCompte($commandeGroupe->getCommandeur());
                                $classe = $eleve->getClasseEleve()->getCodeClasse();
                            } else {
                                $classe = "Adulte";
                            }

                            $sandwichsGroupe = [];
                            $nombreEleve = 0;
                            foreach ($commandeGroupe->getSandwichCommandeGroupes() as $sandwichChoisi) {
                                $nombreEleve = $nombreEleve + $sandwichChoisi->getNombreSandwich();
                                $sandwichsGroupe[] = $sandwichChoisi->getNombreSandwich() . ' ' . $sandwichChoisi->getSandwichChoisi()->getNomSandwich();
                            }

                            $commandeGroupeRow[] = [
                                'Date et heure de Livraison' => $commandeGroupe->getDateHeureLivraison()->format('d/m/y h:i'),
                                'Prénom et Nom' => $commandeGroupe->getCommandeur()->getPrenomUser() . ' ' . $commandeGroupe->getCommandeur()->getNomUser(),
                                'Classe' => $classe,
                                'Commande' => $sandwichsGroupe[0] . ', ' . $sandwichsGroupe[1] . ', ' . $nombreEleve . ' ' . $commandeGroupe->getBoissonChoisie()->getNomBoisson() . ', ' . $nombreEleve . ' ' . $commandeGroupe->getDessertChoisi()->getNomDessert(),
                                'Chips' => $nombreEleve . ' Chips',
                            ];
                        }
                    }

                    $encoder = new ExcelEncoder($defaultContext = []);

                    if ($commandeGroupeRow != [] || $commandeRow != []) {
                        $commandesRegroupe = array_merge($commandeRow, $commandeGroupeRow);
                        $data = [
                            // Array by sheet
                            'Feuille 1' => $commandesRegroupe
                        ];
                    } else {
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
                    file_put_contents('commande_separé_' . $dateChoisi->format('d-m-y') . '.xlsx', $xls);
                    $filename = 'commande_separé_' . $dateChoisi->format('d-m-y') . '.xlsx';

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

                } elseif ($modalite == "Regroupé") {
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
                    foreach ($sandwichDispo as $sandwich) {
                        $nomSandwich[] = $sandwich->getNomSandwich();
                        $nombreSandwich = 0;
                        if ($commandesGroupeExport != null) {
                            foreach ($commandesGroupeExport as $commandeGroupe) {
                                foreach ($commandeGroupe->getSandwichCommandeGroupes() as $sandwichComGroupe) {
                                    if ($sandwichComGroupe->getSandwichChoisi()->getId() == $sandwich->getId()) {
                                        $nombreSandwich = $sandwichComGroupe->getNombreSandwich();
                                    }
                                }
                            }
                        }
                        if ($commandesExport != null) {
                            $nbSandwich[] = count($comIndRepo->findBySandwich($sandwich->getId(), $dateChoisi)) + $nombreSandwich;
                        }
                    }

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
                            $nbChips = $nbChips + $nombreEleve;
                        }
                        if ($commandesExport != null) {
                            $nbDessert[] = count($comIndRepo->findByDessert($dessert->getId(), $dateChoisi)) + $nombreEleve;
                        }
                    }
                    $dataRowSandwich = [];
                    for ($i = 0; $i < count($nomSandwich); $i++) {
                        $dataRowSandwich[$i] = [
                            'Nom de produit' => $nomSandwich[$i],
                            'Nombre de produit' => $nbSandwich[$i],
                        ];
                    }

                    $dataRowBoisson = [];
                    for ($i = 0; $i < count($nomBoisson); $i++) {
                        $dataRowBoisson[$i] = [
                            'Nom de produit' => $nomBoisson[$i],
                            'Nombre de produit' => $nbBoisson[$i],
                        ];
                    }

                    $dataRowDessert = [];
                    for ($i = 0; $i < count($nomDessert); $i++) {
                        $dataRowDessert[$i] = [
                            'Nom de produit' => $nomDessert[$i],
                            'Nombre de produit' => $nbDessert[$i],
                        ];
                    }

                    if ($commandesExport) {
                        foreach ($commandesExport as $commande) {
                            if ($commande->getPrendreChips()) {
                                $nbChips++;
                            }
                        }
                    }

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

                    $encoder = new ExcelEncoder($defaultContext = []);

                    // Test data
                    $data = [
                        // Array by sheet
                        'Feuille 1' => $dataRow
                    ];

                    // Encode data with specific format
                    $xls = $encoder->encode($data, ExcelEncoder::XLSX);
                    $dateChoisi = $exportReq->get('dateExport')->getData();

                    // Put the content in a file with format extension for example
                    file_put_contents('commande_regroupé_' . $dateChoisi->format('d-m-y') . '.xlsx', $xls);
                    $filename = 'commande_regroupé_' . $dateChoisi->format('d-m-y') . '.xlsx';

                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header("Cache-Control: no-cache, must-revalidate");
                    header("Expires: 0");
                    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                    header('Content-Length: ' . filesize($filename));
                    header('Pragma: public');
                    readfile($filename);

                    rename($filename, $this->getParameter('excelFile_directory') . $filename);
                }
                return new Response();
            } elseif ($methode == "Impression") {
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

        $form = $this->createForm(FilterAdminCommandeType::class);

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

        $commandes = $paginator->paginate(
            $commandes,
            $request->query->getInt('page', 1),
            25
        );

        $commandesGroupe = $paginator->paginate(
            $commandesGroupe,
            $request->query->getInt('page', 1),
            5
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
     * @Route("/pdf", name="commande_pdf", methods={"GET","POST"})
     */
    public function pdfDownload($commandes, $commandesGroupe, $modalite, $dateChoisi): Response
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

        // Génère le pdf et le rendu html à partir du TWIG
        if ($modalite == "Séparé") {
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
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $date = $dateChoisi->format('d-m-Y');

        // Nomme le fichier PDF
        $fichier = 'Commande_' . $modalite . '_' . $date . '.pdf';

        // Télécharge le pdf
        $dompdf->stream($fichier, [
            'Attachment' => true
        ]);

        // Retourne le résultat
        return new Response();

    }

    /**
     * @param $modalite
     * @param $dateChoisi
     * @param $affichage
     * @return Response
     * @Route("/preview", name="commande_impression", methods={"GET","POST"})
     */
    public function printPreview($modalite, $dateChoisi, $affichage): Response
    {

        if ($affichage == "les deux" || $affichage == "individuelle") {
            $commandes = $this->comIndRepo
                ->exportationCommande($dateChoisi->format('y-m-d'));
        } else {
            $commandes = null;
        }

        if ($affichage == "les deux" || $affichage == "groupé") {

            $commandeGroupe = $this->comGrRepo->exportationCommandeGroupe($dateChoisi->format('y-m-d'));
        } else {
            $commandeGroupe = null;
        }

        if ($modalite == "Séparé") {
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
     * @Route("/new", name="commande_individuelle_new", methods={"GET", "POST"})
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
                        EleveRepository                 $eleveRepository,
                        UserRepository                  $userRepo): Response
    {

        $user = $userRepo->find($this->getUser());
        $roles = $user->getRoles();
        $cantine = null;
        $dateNow = new DateTime('now', new DateTimeZone('Europe/Paris'));

        if (in_array("ROLE_ELEVE", $roles)) {
            $eleve = $eleveRepository->findOneByCompte($user);
            $cantine = $cantineRepository->findOneByEleve($eleve->getId());
        }

        $limiteJourMeme = $limiteRepo->findOneById(1);
        $limite = new DateTime('now ' . $limiteJourMeme->getHeureLimite()->format('h:i'), new DateTimeZone('Europe/Paris'));

        $limiteNbJour = $limiteRepo->findOneById(2);
        $limiteNbSemaine = $limiteRepo->findOneById(3);
        $limiteNbMois = $limiteRepo->findOneById(4);
        $nbCommandeJournalier = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 day 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeSemaine = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 week 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeMois = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 month 23:59:00', new DateTimezone('Europe/Paris'))));

        $deactive = $deactiveRepo->findOneBy(['id' => 1]);
        $sandwichs = $sandwichRepo->findByDispo(true);
        $boissons = $boissonRepo->findByDispo(true);
        $desserts = $dessertRepo->findByDispo(true);
        $commandeIndividuelle = new CommandeIndividuelle();
        $form = $this->createForm(CommandeIndividuelleType::class, $commandeIndividuelle);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $dateLivraison = $form->get('dateHeureLivraison')->getData();
            if (!in_array("ROLE_ADMIN", $roles) &&
                $limiteJourMeme->getIsActive() && $limite < $dateNow &&
                $dateLivraison > new DateTime('now 00:00:00',
                    new DateTimeZone('Europe/Paris')) &&
                $dateLivraison < new DateTime('now 23:59:59',
                    new DateTimeZone('Europe/Paris'))) {
                $this->addFlash(
                    'limiteCloture',
                    'Vous avez dépassé l\'heure de clôture pour les commandes d\'aujourd\'hui !'
                );
            } elseif (!in_array("ROLE_ADMIN", $roles) && $dateLivraison->format('l') == "Saturday" or $dateLivraison->format('l') == "Sunday") {
                $this->addFlash(
                    'limiteCloture',
                    'Vous ne pouvez pas faire une commande pour le samedi ou pour le dimanche !'
                );
            } else {
                $error = false;
                if (in_array("ROLE_ELEVE", $roles)) {
                    switch ($dateLivraison->format('l')) {
                        case "Monday":
                            if (!$cantine->getRepasJ1()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le lundi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                        case "Tuesday":
                            if (!$cantine->getRepasJ2()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le mardi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                        case "Wednesday":
                            if (!$cantine->getRepasJ3()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le mercredi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                        case "Thursday":
                            if (!$cantine->getRepasJ4()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le jeudi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                        case "Friday":
                            if (!$cantine->getRepasJ5()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le vendredi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                    }
                }
                if (!$error) {
                    $raisonPrecis = $form->get('raisonCommandeAutre')->getData();
                    if ($form->get('raisonCommande')->getData() == "Autre" && $raisonPrecis == "Ajouter text") {
                        $this->addFlash(
                            'precisionReason',
                            'Veuillez préciser votre raison !'
                        );
                    } else {
                        if ($form->get('raisonCommande')->getData() == "Autre") {
                            $commandeIndividuelle->setRaisonCommande($raisonPrecis);
                        } else {
                            $commandeIndividuelle->setRaisonCommande($form->get('raisonCommande')->getData());
                        }
                        $commandeIndividuelle
                            ->setCommandeur($user)
                            ->setDateCreation($dateNow)
                            ->setEstValide(true);
                        $entityManager->persist($commandeIndividuelle);
                        $entityManager->flush();

                        $this->addFlash(
                            'SuccessComInd',
                            'Votre commande a été sauvegardée !'
                        );
                    }
                }
            }
            return $this->redirectToRoute('commande_individuelle_new', [], Response::HTTP_SEE_OTHER);
        }


        if ($deactive->getIsDeactivated() === true) {
            return $this->redirectToRoute('deactivate_commande');
        } else {
            return $this->renderForm('commande_individuelle/new.html.twig', [
                'commande_individuelle' => $commandeIndividuelle,
                'form' => $form,
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
     * @Route("/desactive", name="deactivate_commande",methods={"GET"})
     */
    public function deactivated(): Response
    {
        return $this->render(
            'commande_individuelle/deactive.html.twig'
        );
    }

    /**
     * @Route("/validate/{id}", name="validate_commande",methods={"GET","POST"})
     */
    public function validateCommande(CommandeIndividuelle $commande, EntityManagerInterface $entityManager): RedirectResponse
    {
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
     * @Route("/{id}/delete_view", name="commande_individuelle_delete_view", methods={"GET","POST"})
     */
    public function delete_view(CommandeIndividuelle $commandeIndividuelle): Response
    {
        return $this->render('commande_individuelle/delete_view.html.twig', [
            'commande' => $commandeIndividuelle,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="commande_individuelle_edit", methods={"GET", "POST"})
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
                         UserRepository                  $userRepo): Response
    {
        $user = $userRepo->find($this->getUser());
        $roles = $user->getRoles();
        $cantine = null;

        if (in_array("ROLE_ELEVE", $roles)) {
            $cantine = $cantineRepository->findOneByEleve($user->getEleves()->first());
        }
        $limiteJourMeme = $limiteRepo->findOneById(1);
        $limite = new DateTime('now ' . $limiteJourMeme->getHeureLimite()->format('h:i'), new DateTimeZone('Europe/Paris'));
        $dateNow = new DateTime('now', new DateTimeZone('Europe/Paris'));
        $limiteNbJour = $limiteRepo->findOneById(2);
        $limiteNbSemaine = $limiteRepo->findOneById(3);
        $limiteNbMois = $limiteRepo->findOneById(4);
        $nbCommandeJournalier = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 day 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeSemaine = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 week 23:59:00', new DateTimezone('Europe/Paris'))));
        $nbCommandeMois = count($this->comIndRepo->findBetweenDate($user, new DateTime('now 00:00:00', new DateTimezone('Europe/Paris')), new DateTime('+1 month 23:59:00', new DateTimezone('Europe/Paris'))));

        $deactive = $deactiveRepo->findOneBy(['id' => 1]);
        $sandwichs = $sandwichRepo->findByDispo(true);
        $boissons = $boissonRepo->findByDispo(true);
        $desserts = $dessertRepo->findByDispo(true);
        $form = $this->createForm(CommandeIndividuelleType::class, $commandeIndividuelle);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $dateLivraison = $form->get('dateHeureLivraison')->getData();
            if (!in_array("ROLE_ADMIN", $roles) &&
                $limiteJourMeme->getIsActive() && $limite < $dateNow &&
                $dateLivraison > new DateTime('now 00:00:00',
                    new DateTimeZone('Europe/Paris')) &&
                $dateLivraison < new DateTime('now 23:59:59',
                    new DateTimeZone('Europe/Paris'))) {
                $this->addFlash(
                    'limiteCloture',
                    'Vous avez dépassé l\'heure de clôture pour les commandes d\'aujourd\'hui !'
                );
            } elseif ($dateLivraison->format('l') == "Saturday" or $dateLivraison->format('l') == "Sunday") {
                $this->addFlash(
                    'limiteCloture',
                    'Vous ne pouvez pas faire une commande le samedi et le dimanche !'
                );
            } else {
                $error = false;
                if (in_array("ROLE_ELEVE", $roles)) {
                    switch ($dateLivraison->format('l')) {
                        case "Monday":
                            if (!$cantine->getRepasJ1()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le lundi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                        case "Tuesday":
                            if (!$cantine->getRepasJ2()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le mardi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                        case "Wednesday":
                            if (!$cantine->getRepasJ3()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le mercredi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                        case "Thursday":
                            if (!$cantine->getRepasJ4()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le jeudi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                        case "Friday":
                            if (!$cantine->getRepasJ5()) {
                                $this->addFlash(
                                    'limiteCloture',
                                    'Vous n\'êtes pas inscrit le vendredi à la cantine !'
                                );
                                $error = true;
                            }
                            break;
                    }
                }
                if (!$error) {
                    $raisonPrecis = $form->get('raisonCommandeAutre')->getData();
                    if ($form->get('raisonCommande')->getData() == "Autre" && $raisonPrecis == "Ajouter text") {
                        $this->addFlash(
                            'precisionReason',
                            'Veuillez préciser votre raison !'
                        );
                    } else {
                        if ($form->get('raisonCommande')->getData() == "Autre") {
                            $commandeIndividuelle->setRaisonCommande($raisonPrecis);
                        } else {
                            $commandeIndividuelle->setRaisonCommande($form->get('raisonCommande')->getData());
                        }
                        $commandeIndividuelle
                            ->setCommandeur($user)
                            ->setDateCreation($dateNow)
                            ->setEstValide(true);

                        $entityManager->flush();
                        $this->addFlash(
                            'SuccessComInd',
                            'Votre commande a été modifié !'
                        );
                    }
                }
            }

            return $this->redirectToRoute('commande_individuelle_edit',
                ['id' => $commandeIndividuelle->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($deactive->getIsDeactivated() === true) {
            return $this->redirectToRoute('deactivate_commande');
        } else {
            return $this->renderForm('commande_individuelle/edit.html.twig', [
                'commande_individuelle' => $commandeIndividuelle,
                'form' => $form,
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
     * @Route("/{id}", name="commande_individuelle_delete", methods={"POST"})
     */
    public function delete(Request                $request,
                           CommandeIndividuelle   $commandeIndividuelle,
                           EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commandeIndividuelle->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commandeIndividuelle);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteComInd',
                'La commande a été annulée !'
            );
        }

        return $this->redirectToRoute('commande_individuelle_index', [], Response::HTTP_SEE_OTHER);
    }
}
