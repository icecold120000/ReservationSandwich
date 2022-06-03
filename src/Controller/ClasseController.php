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
     * @Route("/index/{page}",defaults={"page" : 1}, name="classe_index", methods={"GET","POST"})
     */
    public function index(ClasseRepository   $classeRepos,
                          PaginatorInterface $paginator,
                          Request            $request,
                                             $page = 1): Response
    {
        $classes = $classeRepos->filterClasse('ASC');
        $form = $this->createForm(FilterClasseType::class, null, ['method' => 'GET']);
        $filter = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $classes = $classeRepos->filterClasse(
                $filter->get('ordreAlphabet')->getData(),
                $filter->get('searchClasse')->getData()
            );
        }

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
     * @Route("/new", name="classe_new", methods={"GET","POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $classe = new Classe();
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($classe);
            $entityManager->flush();

            $this->addFlash(
                'SuccessClasse',
                'La classe a été sauvegardée !'
            );

            return $this->redirectToRoute('classe_new');
        }

        return $this->render('classe/new.html.twig', [
            'classe' => $classe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show/{id}", name="classe_show", methods={"GET","POST"})
     * @throws NonUniqueResultException
     */
    public function show(Classe                       $classe,
                         Request                      $request,
                         EleveRepository              $eleveRepo,
                         InscriptionCantineRepository $cantineRepository,
                                                      $page = 1): Response
    {
        $eleves = $classe->getEleves();
        $form = $this->createForm(OrderEleveType::class);
        $search = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eleves = $eleveRepo->orderByEleve(
                $search->get('ordreNom')->getData(),
                $search->get('ordrePrenom')->getData(),
                $classe
            );
        }

        $cantineInscrit = [];
        foreach ($eleves as $eleve) {
            $cantineInscrit[] = $cantineRepository->findOneByEleve($eleve->getId());
        }

        return $this->render('classe/show.html.twig', [
            'eleves' => $eleves,
            'classe' => $classe,
            'cantineInscrits' => $cantineInscrit,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="classe_edit", methods={"GET","POST"})
     */
    public function edit(Request                $request,
                         Classe                 $classe,
                         EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClasseType::class, $classe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'SuccessClasse',
                'La classe a été modifiée !'
            );

            return $this->redirectToRoute('classe_edit', array('id' => $classe->getId()));
        }

        return $this->render('classe/edit.html.twig', [
            'classe' => $classe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete_view", name="classe_delete_view", methods={"GET","POST"})
     */
    public function delete_view(Classe $classe): Response
    {
        return $this->render('classe/delete_view.html.twig', [
            'classe' => $classe,
        ]);
    }

    /**
     * @Route("/{id}", name="classe_delete", methods={"GET","POST","DELETE"})
     */
    public function delete(Request                $request,
                           Classe                 $classe,
                           EleveRepository        $eleveRepo,
                           EntityManagerInterface $entityManager): Response
    {
        $eleveRelated = $eleveRepo->findByClasse(null, $classe);
        if ($eleveRelated) {
            $this->addFlash(
                'deleteDangerClasse',
                'Erreur, impossible de supprimer cette classe.
                Veuillez vérifier que tous les élèves appartenant à celle-ci soient changés.'
            );
            return $this->redirectToRoute('classe_delete_view',
                array('id' => $classe->getId()));
        } else {
            if ($this->isCsrfTokenValid('delete' . $classe->getId(),
                $request->request->get('_token'))) {
                $entityManager->remove($classe);
                $entityManager->flush();
                $this->addFlash(
                    'SuccessDeleteClasse',
                    'La classe a été supprimée !'
                );
            }
        }

        return $this->redirectToRoute('classe_index');
    }
}
