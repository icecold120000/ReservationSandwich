<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Form\ClasseType;
use App\Form\FilterOrSearch\OrderEleveType;
use App\Form\FilterOrSearch\FilterClasseType;
use App\Repository\ClasseRepository;
use App\Repository\EleveRepository;
use App\Repository\InscriptionCantineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @Route("/classe")
 */
class ClasseController extends AbstractController
{
    /**
     * Page de gestion des classes
     * @Route("/index/{page}",defaults={"page" : 1}, name="classe_index", methods={"GET","POST"})
     * @param ClasseRepository $classeRepos
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @param int $page Utilisé pour les filtres et la pagination
     * @return Response
     */
    public function index(ClasseRepository   $classeRepos,
                          PaginatorInterface $paginator,
                          Request            $request,
                          int                $page = 1): Response
    {
        /*Récupération des classes*/
        $classes = $classeRepos->filterClasse('ASC');
        $form = $this->createForm(FilterClasseType::class, null, ['method' => 'GET']);
        $filter = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $classes = $classeRepos->filterClasse(
                $filter->get('ordreAlphabet')->getData(),
                $filter->get('searchClasse')->getData()
            );
        }

        /*Pagination*/
        $classes = $paginator->paginate(
            $classes,
            $page,
            10
        );

        return $this->render('classe/index.html.twig', [
            'classes' => $classes,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire d'ajout d'une classe
     * @Route("/new", name="classe_new", methods={"GET","POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ClasseRepository $classeRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function new(Request                $request,
                        EntityManagerInterface $entityManager,
                        ClasseRepository       $classeRepo): Response
    {
        $classe = new Classe();
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Vérifie si la classe saisie existe déjà par son libellé ou par code*/
            $foundLib = $classeRepo->findOneByLibelle($form->get('libelle')->getData());
            $foundCode = $classeRepo->findOneByCode($form->get('codeClasse')->getData());

            /*
              Si une classe saisie est trouvée avec ce libellé alors un message d'erreur s'affiche
              Sinon si une classe saisie est trouvée avec ce code de classe alors un message d'erreur s'affiche
              Sinon la classe est créée
             */
            if ($foundLib) {
                $this->addFlash(
                    'ErreurClasseLib',
                    'La classe saisie existe déjà avec ce libellé !'
                );
            } elseif ($foundCode) {
                $this->addFlash(
                    'ErreurClasseLib',
                    'La classe saisie existe déjà avec ce code de classe !'
                );
            } else {
                $entityManager->persist($classe);
                $entityManager->flush();

                $this->addFlash(
                    'SuccessClasse',
                    'La classe a été sauvegardée !'
                );
            }
            return $this->redirectToRoute('classe_new');
        }

        return $this->render('classe/new.html.twig', [
            'classe' => $classe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de détail d'une classe
     * @Route("/show/{id}/{page}",defaults={"page" : 1}, name="classe_show", methods={"GET","POST"})
     * @param Classe $classe
     * @param Request $request
     * @param EleveRepository $eleveRepo
     * @param InscriptionCantineRepository $cantineRepository
     * @param PaginatorInterface $paginator
     * @param int $page
     * @return Response
     * @throws NonUniqueResultException
     */
    public function show(Classe                       $classe,
                         Request                      $request,
                         EleveRepository              $eleveRepo,
                         InscriptionCantineRepository $cantineRepository,
                         PaginatorInterface           $paginator,
                         int                          $page = 1): Response
    {
        /*Récupération des élèves de la classe*/
        $eleves = $classe->getEleves();
        $form = $this->createForm(OrderEleveType::class, null, ['method' => 'GET']);
        $search = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $eleves = $eleveRepo->orderByEleve(
                $search->get('ordreNom')->getData(),
                $search->get('ordrePrenom')->getData(),
                $classe
            );
        }

        /*Récupération des inscriptions à la cantine par élèves*/
        $cantineInscrit = [];
        foreach ($eleves as $eleve) {
            $cantineInscrit[] = $cantineRepository->findOneByEleve($eleve->getId());
        }

        /*Pagination*/
        $eleves = $paginator->paginate(
            $eleves,
            $page,
            40
        );

        return $this->render('classe/show.html.twig', [
            'eleves' => $eleves,
            'classe' => $classe,
            'cantineInscrits' => $cantineInscrit,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de modification d'une classe
     * @Route("/{id}/edit", name="classe_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Classe $classe
     * @param EntityManagerInterface $entityManager
     * @param ClasseRepository $classeRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function edit(Request                $request,
                         Classe                 $classe,
                         EntityManagerInterface $entityManager,
                         ClasseRepository       $classeRepo): Response
    {
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Vérifie si la classe saisie existe déjà par son libellé ou par code*/
            $foundLib = $classeRepo->findOneByLibelle($form->get('libelle')->getData());
            $foundCode = $classeRepo->findOneByCode($form->get('codeClasse')->getData());

            /*
              Si une classe saisie est trouvée avec ce libellé et que libellé a changé alors un message d'erreur s'affiche
              Sinon si une classe saisie est trouvée avec ce code de classe et que le code de classe a changé alors un message d'erreur s'affiche
              Sinon la classe est créée
             */
            if ($foundLib && $classe->getLibelleClasse() != $classeRepo->findOneByLibelle($form->get('libelle')->getData())) {
                $this->addFlash(
                    'ErreurClasseLib',
                    'La classe saisie existe déjà avec ce libellé !'
                );
            } elseif ($foundCode && $classe->getCodeClasse() != $classeRepo->findOneByCode($form->get('codeClasse')->getData())) {
                $this->addFlash(
                    'ErreurClasseLib',
                    'La classe saisie existe déjà avec ce code de classe !'
                );
            } else {
                $entityManager->flush();

                $this->addFlash(
                    'SuccessClasse',
                    'La classe a été modifiée !'
                );
            }

            return $this->redirectToRoute('classe_edit', array('id' => $classe->getId()));
        }

        return $this->render('classe/edit.html.twig', [
            'classe' => $classe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de pré-suppression d'une classe
     * @Route("/{id}/delete_view", name="classe_delete_view", methods={"GET","POST"})
     * @param Classe $classe
     * @return Response
     */
    public function delete_view(Classe $classe): Response
    {
        return $this->render('classe/delete_view.html.twig', [
            'classe' => $classe,
        ]);
    }

    /**
     * Formulaire de suppression d'une classe
     * @Route("/{id}", name="classe_delete", methods={"GET","POST","DELETE"})
     * @param Request $request
     * @param Classe $classe
     * @param EleveRepository $eleveRepo
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(Request                $request,
                           Classe                 $classe,
                           EleveRepository        $eleveRepo,
                           EntityManagerInterface $entityManager): Response
    {
        /*Récupération des élèves d'une classe*/
        $eleveRelated = $eleveRepo->findByClasse(null, $classe);

        /*Vérifie si cette classe contient toujours des élèves*/
        if ($eleveRelated) {
            //si oui renvoie un message d'erreur
            $this->addFlash(
                'deleteDangerClasse',
                'Erreur, impossible de supprimer cette classe.
                Veuillez vérifier que tous les élèves appartenant à celle-ci soient changés.'
            );
            return $this->redirectToRoute('classe_delete_view',
                array('id' => $classe->getId()));
        } else {
            //sinon supprime la classe
            if ($this->isCsrfTokenValid('delete' . $classe->getId(),
                $request->request->get('_token'))) {
                $entityManager->remove($classe);
                $entityManager->flush();

                /*Message d'erreur*/
                $this->addFlash(
                    'SuccessDeleteClasse',
                    'La classe a été supprimée !'
                );
            }
        }

        return $this->redirectToRoute('classe_index');
    }
}
