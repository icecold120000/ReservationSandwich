<?php

namespace App\Controller;

use Ang3\Component\Serializer\Encoder\ExcelEncoder;
use App\Entity\CommandeIndividuelle;
use App\Form\CommandeIndividuelleType;
use App\Form\FilterOrSearch\FilterAdminCommandeType;
use App\Form\FilterOrSearch\FilterExportationType;
use App\Form\FilterOrSearch\FilterIndexCommandeType;
use App\Repository\BoissonRepository;
use App\Repository\CommandeIndividuelleRepository;
use App\Repository\DessertRepository;
use App\Repository\SandwichRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    public function __construct(SandwichRepository $sandwichRepo,
                                BoissonRepository $boissonRepo, DessertRepository $dessertRepo,
                                CommandeIndividuelleRepository $comIndRepo) {
        $this->sandwichRepo = $sandwichRepo;
        $this->boissonRepo = $boissonRepo;
        $this->dessertRepo = $dessertRepo;
        $this->comIndRepo = $comIndRepo;
    }

    /**
     * @Route("/", name="commande_individuelle_index", methods={"GET","POST"})
     */
    public function index(CommandeIndividuelleRepository $comIndRepo,
                          PaginatorInterface $paginator, Request $request): Response
    {
        $commandes = $comIndRepo->findIndexAllNonCloture($this->getUser());

        $form = $this->createForm(FilterIndexCommandeType::class);
        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandes = $comIndRepo->filterIndex(
                $this->getUser(),
                $filter->get('date')->getData()
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
        ]);
    }

    /**
     * @Route("/admin", name="commande_individuelle_admin", methods={"GET","POST"})
     * @throws Exception
     */
    public function admin(CommandeIndividuelleRepository $comIndRepo,
                          PaginatorInterface $paginator, Request $request): Response
    {

        $commandes = $comIndRepo->findAllNonCloture();

        $export = $this->createForm(FilterExportationType::class);
        $exportReq = $export->handleRequest($request);

        if ($export->isSubmitted() && $export->isValid()) {

            $dateChoisi = $exportReq->get('dateExport')->getData();

            $commandesExport = $comIndRepo
                ->exportationCommande($dateChoisi->format("y-m-d"));
            $modalite = $exportReq->get('modaliteCommande')->getData();

            $methode = $exportReq->get('methodeExport')->getData();
            if ($methode == "PDF") {
                CommandeIndividuelleController::pdfDownload($commandesExport,$modalite,$dateChoisi);
            }
            elseif ($methode == "Excel") {
                $sandwichDispo = $this->sandwichRepo->findByDispo(true);
                $boissonDispo = $this->boissonRepo->findByDispo(true);
                $dessertDispo = $this->dessertRepo->findByDispo(true);

                $nbChips = 0;
                foreach ($commandesExport as $commande) {
                    if ($commande->getPrendreChips() == true) {
                        $nbChips++;
                    }
                }

                $encoder = new ExcelEncoder($defaultContext = []);
                // Test data
                $data = [
                    // Array by sheet
                    'Feuille 1' => [
                        // Array by rows
                        [
                            'Nom du produit',
                            'Nombre de produit',
                        ],
                        [
                            'Nom du produit ' => 'Sandwich 1',
                            'Nombre de produit' => 'Nombre de Sandwich 1',
                        ],
                        [
                            'Nom du produit',
                            'Nombre de produit',
                        ],
                        [
                            'Nom du produit',
                            'Nombre de produit',
                        ],
                        [
                            'Nom du produit' => 'Chips',
                            'Nombre de produit' => $nbChips,
                        ],
                    ]
                ];

                // Encode data with specific format
                $xls = $encoder->encode($data, ExcelEncoder::XLSX);

                // Put the content in a file with format extension for example
                file_put_contents('my_excel_file.xlsx', $xls);

            }
            else {
                /*TODO*/
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
        else {
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

        // Télécharge le pdf et l'ouvre dans un onglet
        $dompdf->stream($fichier, [
            'Attachment' => true
        ]);

        // Retourne le résultat
        return new Response();
    }

    /**
     * @Route("/new", name="commande_individuelle_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager,
                        SandwichRepository $sandwichRepo, BoissonRepository $boissonRepo,
                        DessertRepository $dessertRepo): Response
    {
        $sandwichs = $sandwichRepo->findByDispo(true);
        $boissons = $boissonRepo->findByDispo(true);
        $desserts = $dessertRepo->findByDispo(true);
        $commandeIndividuelle = new CommandeIndividuelle();
        $form = $this->createForm(CommandeIndividuelleType::class, $commandeIndividuelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('raisonCommande')->getData() == "Autres (à préciser)") {
                $commandeIndividuelle->setRaisonCommande($form->get('raisonCommandeAutre')->getData());
            }
            $commandeIndividuelle->setCommandeur($this->getUser());
            $entityManager->persist($commandeIndividuelle);
            $entityManager->flush();

            $this->addFlash(
                'SuccessComInd',
                'Votre commande a été sauvegardée !'
            );

            return $this->redirectToRoute('commande_individuelle_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('commande_individuelle/new.html.twig', [
            'commande_individuelle' => $commandeIndividuelle,
            'form' => $form,
            'sandwichs' => $sandwichs,
            'boissons' => $boissons,
            'desserts' => $desserts,
        ]);
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
