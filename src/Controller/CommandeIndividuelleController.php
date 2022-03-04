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
use App\Repository\CommandeIndividuelleRepository;
use App\Repository\DesactivationCommandeRepository;
use App\Repository\DessertRepository;
use App\Repository\EleveRepository;
use App\Repository\LimitationCommandeRepository;
use App\Repository\SandwichRepository;
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
use Symfony\Component\Validator\Constraints\Timezone;

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

    public function __construct(SandwichRepository $sandwichRepo,
                                BoissonRepository $boissonRepo, DessertRepository $dessertRepo,
                                CommandeIndividuelleRepository $comIndRepo,
                                EleveRepository $eleveRepo) {
        $this->sandwichRepo = $sandwichRepo;
        $this->boissonRepo = $boissonRepo;
        $this->dessertRepo = $dessertRepo;
        $this->comIndRepo = $comIndRepo;
        $this->eleveRepo = $eleveRepo;
    }

    /**
     * @Route("/", name="commande_individuelle_index", methods={"GET","POST"})
     * @throws NonUniqueResultException
     */
    public function index(CommandeIndividuelleRepository $comIndRepo,
                          PaginatorInterface $paginator, Request $request,
                          LimitationCommandeRepository $limiteRepo): Response
    {
        $limiteJourMeme = $limiteRepo->findOneByLibelle("clôture");
        $limiteNbJour = $limiteRepo->findOneByLibelle("journalier");
        $limiteNbSemaine = $limiteRepo->findOneByLibelle("hebdomadaire");
        $limiteNbMois = $limiteRepo->findOneByLibelle("mensuel");
        $nbCommandeJournalier = count($comIndRepo->findBetweenDate($this->getUser(), new \DateTime('now 00:00:00', new \DateTimezone('Europe/Paris')), new \DateTime('+1 day 23:59:00',new \DateTimezone('Europe/Paris'))));
        $nbCommandeSemaine = count($comIndRepo->findBetweenDate($this->getUser(),new \DateTime('now 00:00:00',new \DateTimezone('Europe/Paris')),new \DateTime('+1 week 23:59:00',new \DateTimezone('Europe/Paris'))));
        $nbCommandeMois = count($comIndRepo->findBetweenDate($this->getUser(),new \DateTime('now 00:00:00',new \DateTimezone('Europe/Paris')),new \DateTime('+1 month 23:59:00',new \DateTimezone('Europe/Paris'))));
        $limiteDate = new \DateTime('now '.$limiteJourMeme->getHeureLimite()->format('h:i'),
            new \DateTimeZone('Europe/Paris'));
        $commandes = $comIndRepo->findIndexAllNonCloture($this->getUser());

        $form = $this->createForm(FilterIndexCommandeType::class);
        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandes = $this->comIndRepo->filterIndex(
                $this->getUser(),
                $filter->get('date')->getData(),
                $filter->get('cloture')->getData()
            );
        }

        $commandes = $paginator->paginate(
            $commandes,
            $request->query->getInt('page',1),
            25
        );

        return $this->render('commande_individuelle/index.html.twig', [
            'commandes_ind' => $commandes,
            'form' => $form->createView(),
            'limite' => $limiteDate,
            'limiteActive' => $limiteJourMeme->getIsActive(),
            'limiteNbJournalier' => $limiteNbJour->getNbLimite(),
            'limiteActiveNbJour' => $limiteNbJour->getIsActive(),
            'limiteNbSemaine'=> $limiteNbSemaine->getNbLimite(),
            'limiteActiveNbSemaine'=> $limiteNbSemaine->getIsActive(),
            'limiteNbMois'=> $limiteNbMois->getNbLimite(),
            'limiteActiveNbMois'=> $limiteNbMois->getIsActive(),
            'nbCommandeJournalier' => $nbCommandeJournalier,
            'nbCommandeSemaine' => $nbCommandeSemaine,
            'nbCommandeMois' => $nbCommandeMois,
        ]);
    }

    /**
     * @Route("/desactivation/{desactiveId}", name="commande_ind_desactive", methods={"GET","POST"})
     * @Entity("desactivationCommande", expr="repository.find(desactiveId)")
     */
    public function deactivation(DesactivationCommande $desactiveId, EntityManagerInterface $manager): RedirectResponse
    {

        if ($desactiveId->getIsDeactivated() === false) {
            $desactiveId->setIsDeactivated(true);
            $this->addFlash(
                'SuccessDeactivation',
                'La page de réservation a été désactivée !'
            );
        }
        elseif ($desactiveId->getIsDeactivated() === true) {
            $desactiveId->setIsDeactivated(false);
            $this->addFlash(
                'SuccessDeactivation',
                'La page de réservation a été réactivée !'
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
     */
    public function admin(CommandeIndividuelleRepository $comIndRepo,
                          PaginatorInterface $paginator, Request $request): Response
    {

        $commandes = $comIndRepo->findAllNonCloture();

        $export = $this->createForm(FilterExportationType::class);
        $exportReq = $export->handleRequest($request);

        if ($export->isSubmitted() && $export->isValid()) {

            $dateChoisi = $exportReq->get('dateExport')->getData();
            $dateChoisi = $dateChoisi->format('y-m-d');

            $commandesExport = $comIndRepo
                ->exportationCommande($dateChoisi);
            $modalite = $exportReq->get('modaliteCommande')->getData();

            $methode = $exportReq->get('methodeExport')->getData();
            if ($methode == "PDF") {
                CommandeIndividuelleController::pdfDownload($commandesExport,$modalite,$exportReq->get('dateExport')->getData());
            }
            elseif ($methode == "Excel") {

                if ($modalite == "Séparé") {
                    $commandeRow = [];
                    foreach ($commandesExport as $commande) {

                        if (in_array(User::ROLE_ELEVE,$commande->getCommandeur()->getRoles()) ) {
                            $eleve = $this->eleveRepo->findOneByCompte($commande->getCommandeur()->getId());
                            $classe = $eleve->getClasseEleve()->getCodeClasse();
                        }
                        else {
                            $classe = "Adulte";
                        }

                        if ($commande->getPrendreChips() == true) {
                            $chips = "Oui";
                        }
                        else {
                            $chips = "Non";
                        }
                        $commandeRow[] = [
                            'Date et heure de Livraison' => $commande->getDateHeureLivraison()->format('d/m/y h:i'),
                            'Prénom et Nom' => $commande->getCommandeur()->getPrenomUser().', '.$commande->getCommandeur()->getNomUser(),
                            'Classe' => $classe,
                            'Commande' => $commande->getSandwichChoisi()->getNomSandwich().', '.$commande->getBoissonChoisie()->getNomBoisson().', '.$commande->getDessertChoisi()->getNomDessert(),
                            'Chips' => $chips,
                        ];
                    }

                    $encoder = new ExcelEncoder($defaultContext = []);

                    // Test data
                    $data = [
                        // Array by sheet
                        'Feuille 1' => $commandeRow
                    ];

                    // Encode data with specific format
                    $xls = $encoder->encode($data, ExcelEncoder::XLSX);
                    $dateChoisi = $exportReq->get('dateExport')->getData();

                    // Put the content in a file with format extension for example
                    file_put_contents('commande_separé_'.$dateChoisi->format('d-m-y').'.xlsx', $xls);
                    $filename = 'commande_separé_'.$dateChoisi->format('d-m-y').'.xlsx';

                    //Permet le téléchargement du fichier
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header("Cache-Control: no-cache, must-revalidate");
                    header("Expires: 0");
                    header('Content-Disposition: attachment; filename="'.basename($filename).'"');
                    header('Content-Length: ' . filesize($filename));
                    header('Pragma: public');
                    readfile($filename);

                    // Déplace le fichier dans le dossier Uploads
                    rename($filename,$this->getParameter('excelFile_directory').'/'.$filename);

                } elseif ($modalite == "Regroupé") {
                    $sandwichDispo = $this->sandwichRepo->findByDispo(true);
                    $boissonDispo = $this->boissonRepo->findByDispo(true);
                    $dessertDispo = $this->dessertRepo->findByDispo(true);
                    $nomSandwich =[];
                    $nbSandwich =[];
                    $nomBoisson = [];
                    $nbBoisson = [];
                    $nomDessert = [];
                    $nbDessert = [];
                    $nbChips = 0;

                    foreach ($sandwichDispo as $sandwich) {
                        $nomSandwich[] = $sandwich->getNomSandwich();
                        $nbSandwich[] = count($comIndRepo->findBySandwich($sandwich->getId(),$dateChoisi));
                    }

                    foreach ($boissonDispo as $boisson) {
                        $nomBoisson[] = $boisson->getNomBoisson();
                        $nbBoisson[] = count($comIndRepo->findByBoisson($boisson->getId(),$dateChoisi));
                    }

                    foreach ($dessertDispo as $dessert) {
                        $nomDessert[] = $dessert->getNomDessert();
                        $nbDessert[] = count($comIndRepo->findByDessert($dessert->getId(),$dateChoisi));
                    }
                    $dataRowSandwich = [];
                    for ($i = 0 ; $i < count($nomSandwich);$i++) {
                        $dataRowSandwich[$i] = [
                            'Nom de produit' => $nomSandwich[$i],
                            'Nombre de produit' => $nbSandwich[$i],
                        ];
                    }

                    $dataRowBoisson = [];
                    for ($i = 0 ; $i < count($nomBoisson);$i++) {
                        $dataRowBoisson[$i] = [
                            'Nom de produit' => $nomBoisson[$i],
                            'Nombre de produit' => $nbBoisson[$i],
                        ];
                    }

                    $dataRowDessert = [];
                    for ($i = 0 ; $i < count($nomDessert);$i++) {
                        $dataRowDessert[$i] = [
                            'Nom de produit' => $nomDessert[$i],
                            'Nombre de produit' => $nbDessert[$i],
                        ];
                    }

                    foreach ($commandesExport as $commande) {
                        if ($commande->getPrendreChips() == true) {
                            $nbChips++;
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
                    file_put_contents('commande_regroupé_'.$dateChoisi->format('d-m-y').'.xlsx', $xls);
                    $filename = 'commande_regroupé_'.$dateChoisi->format('d-m-y').'.xlsx';

                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header("Cache-Control: no-cache, must-revalidate");
                    header("Expires: 0");
                    header('Content-Disposition: attachment; filename="'.basename($filename).'"');
                    header('Content-Length: ' . filesize($filename));
                    header('Pragma: public');
                    readfile($filename);

                    rename($filename,$this->getParameter('excelFile_directory').'/'.$filename);
                }
                return new Response();
            }
            elseif ($methode == "Impression"){
                CommandeIndividuelleController::printPreview($commandesExport,$modalite,$exportReq->get('dateExport')->getData());
            }

        }

        $form = $this->createForm(FilterAdminCommandeType::class);

        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandes = $comIndRepo->filterAdmin(
                $filter->get('nom')->getData(),
                $filter->get('date')->getData(),
                $filter->get('cloture')->getData()
            );
        }

        $commandes = $paginator->paginate(
            $commandes,
            $request->query->getInt('page',1),
            25
        );

        return $this->render('commande_individuelle/admin.html.twig', [
            'commandes_ind' => $commandes,
            'form' => $form->createView(),
            'exportForm' => $export->createView(),
        ]);
    }

    /**
     * @Route("/preview", name="commande_impression", methods={"GET","POST"})
     */
    public function printPreview($commandes, $modalite, $dateChoisi): Response
    {
        if ($modalite == "Séparé") {
            return $this->render('commande_individuelle/pdf/commande_pdf_separe.html.twig',[
                'commandes' => $commandes,
                'dateChoisi' => $dateChoisi,
            ]);
        }
        elseif ($modalite == "Regroupé") {
            return $this->render('commande_individuelle/pdf/commande_pdf_regroupe.html.twig',[
                'commandes' => $commandes,
                'dateChoisi' => $dateChoisi,
                'sandwichDispo' => $this->sandwichRepo->findByDispo(true),
                'boissonDispo' => $this->boissonRepo->findByDispo(true),
                'dessertDispo' => $this->dessertRepo->findByDispo(true),
            ]);
        }
        return new Response();
    }

    /**
     * @Route("/pdf", name="commande_pdf", methods={"GET","POST"})
     */
    public function pdfDownload($commandes, $modalite, $dateChoisi): Response
    {
        // Défini les options du pdf
        $optionsPdf = new OptionsPdf();

        // Donne une police par défaut
        $optionsPdf->set('defaultFont','Arial');
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
            $html = $this->renderView('commande_individuelle/pdf/commande_pdf_separe.html.twig',[
                'commandes' => $commandes,
                'dateChoisi' => $dateChoisi,
            ]);
        }
        elseif ($modalite == "Regroupé") {
            $html = $this->renderView('commande_individuelle/pdf/commande_pdf_regroupe.html.twig',[
                'commandes' => $commandes,
                'dateChoisi' => $dateChoisi,
                'sandwichDispo' => $this->sandwichRepo->findByDispo(true),
                'boissonDispo' => $this->boissonRepo->findByDispo(true),
                'dessertDispo' => $this->dessertRepo->findByDispo(true),
            ]);
        }

        // Génère l'affichage du pdf dans un onglet
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();

        $date = $dateChoisi->format('d-m-Y');

        // Nomme le fichier PDF
        $fichier = 'Commande_'.$modalite.'_'.$date.'.pdf';

        // Télécharge le pdf
        $dompdf->stream($fichier, [
            'Attachment' => true
        ]);

        // Retourne le résultat
        return new Response();

    }

    /**
     * @Route("/new", name="commande_individuelle_new", methods={"GET", "POST"})
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function new(Request $request, EntityManagerInterface $entityManager,
                        SandwichRepository $sandwichRepo, BoissonRepository $boissonRepo,
                        DessertRepository $dessertRepo,
                        DesactivationCommandeRepository $deactiveRepo,
                        LimitationCommandeRepository $limiteRepo): Response
    {
        $limiteJourMeme = $limiteRepo->findOneByLibelle("clôture");
        $limite = new \DateTime('now '.$limiteJourMeme->getHeureLimite()->format('h:i'),new \DateTimeZone('Europe/Paris'));
        $dateNow = new \DateTime('now',new \DateTimeZone('Europe/Paris'));
        $limiteNbJour = $limiteRepo->findOneByLibelle("journalier");
        $limiteNbSemaine = $limiteRepo->findOneByLibelle("hebdomadaire");
        $limiteNbMois = $limiteRepo->findOneByLibelle("mensuel");
        $nbCommandeJournalier = count($this->comIndRepo->findBetweenDate($this->getUser(), new \DateTime('now 00:00:00', new \DateTimezone('Europe/Paris')), new \DateTime('+1 day 23:59:00',new \DateTimezone('Europe/Paris'))));
        $nbCommandeSemaine = count($this->comIndRepo->findBetweenDate($this->getUser(),new \DateTime('now 00:00:00',new \DateTimezone('Europe/Paris')),new \DateTime('+1 week 23:59:00',new \DateTimezone('Europe/Paris'))));
        $nbCommandeMois = count($this->comIndRepo->findBetweenDate($this->getUser(),new \DateTime('now 00:00:00',new \DateTimezone('Europe/Paris')),new \DateTime('+1 month 23:59:00',new \DateTimezone('Europe/Paris'))));

        $deactive = $deactiveRepo->findOneBy(['id' => 1]);
        $sandwichs = $sandwichRepo->findByDispo(true);
        $boissons = $boissonRepo->findByDispo(true);
        $desserts = $dessertRepo->findByDispo(true);
        $commandeIndividuelle = new CommandeIndividuelle();
        $form = $this->createForm(CommandeIndividuelleType::class, $commandeIndividuelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateLivraison = $form->get('dateHeureLivraison')->getData();
            if ($limiteJourMeme->getIsActive() == true && $limite < $dateNow &&
                $dateLivraison > new \DateTime('now 00:00:00',
                    new \DateTimeZone('Europe/Paris')) &&
                $dateLivraison < new \DateTime('now 23:59:59',
                    new \DateTimeZone('Europe/Paris'))) {
                    $this->addFlash(
                        'limiteCloture',
                        'Vous avez dépassé l\'heure de clôture pour les commandes d\'aujourd\'hui !'
                    );
            }
            else {
                if ($form->get('raisonCommande')->getData() == "Autre") {
                    $commandeIndividuelle->setRaisonCommande($form->get('raisonCommandeAutre')->getData());
                }
                $commandeIndividuelle->setCommandeur($this->getUser());
                $commandeIndividuelle->setDateCreation($dateNow);
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
        }
        else {
            return $this->renderForm('commande_individuelle/new.html.twig', [
                'commande_individuelle' => $commandeIndividuelle,
                'form' => $form,
                'sandwichs' => $sandwichs,
                'boissons' => $boissons,
                'desserts' => $desserts,
                'limiteJourMeme' => $dateNow->format('d-m-y H:i'),
                'limiteNbJournalier' => $limiteNbJour->getNbLimite(),
                'limiteActiveNbJour' => $limiteNbJour->getIsActive(),
                'limiteNbSemaine'=> $limiteNbSemaine->getNbLimite(),
                'limiteActiveNbSemaine'=> $limiteNbSemaine->getIsActive(),
                'limiteNbMois'=> $limiteNbMois->getNbLimite(),
                'limiteActiveNbMois'=> $limiteNbMois->getIsActive(),
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
     */
    public function edit(Request $request, CommandeIndividuelle $commandeIndividuelle,
                         EntityManagerInterface $entityManager,
                         SandwichRepository $sandwichRepo, BoissonRepository $boissonRepo,
                         DessertRepository $dessertRepo): Response
    {
        $sandwichs = $sandwichRepo->findByDispo(true);
        $boissons = $boissonRepo->findByDispo(true);
        $desserts = $dessertRepo->findByDispo(true);
        $form = $this->createForm(CommandeIndividuelleType::class, $commandeIndividuelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'SuccessComInd',
                'Votre commande a été modifié !'
            );

            return $this->redirectToRoute('commande_individuelle_edit',
                ['id' => $commandeIndividuelle->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('commande_individuelle/edit.html.twig', [
            'commande_individuelle' => $commandeIndividuelle,
            'form' => $form,
            'sandwichs' => $sandwichs,
            'boissons' => $boissons,
            'desserts' => $desserts,
        ]);
    }

    /**
     * @Route("/{id}", name="commande_individuelle_delete", methods={"POST"})
     */
    public function delete(Request $request, CommandeIndividuelle $commandeIndividuelle, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$commandeIndividuelle->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commandeIndividuelle);
            $entityManager->flush();
        }

        return $this->redirectToRoute('commande_individuelle_index', [], Response::HTTP_SEE_OTHER);
    }
}
