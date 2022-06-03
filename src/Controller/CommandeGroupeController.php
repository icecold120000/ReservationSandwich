<?php

namespace App\Controller;

use App\Entity\CommandeGroupe;
use App\Entity\SandwichCommandeGroupe;
use App\Form\CommandeGroupeType;
use App\Repository\BoissonRepository;
use App\Repository\DesactivationCommandeRepository;
use App\Repository\DessertRepository;
use App\Repository\LimitationCommandeRepository;
use App\Repository\SandwichCommandeGroupeRepository;
use App\Repository\SandwichRepository;
use App\Repository\UserRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/commande/groupe")
 */
class CommandeGroupeController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     * @throws Exception
     * @Route("/new", name="commande_groupe_new", methods={"GET", "POST"})
     */
    public function new(Request                         $request,
                        EntityManagerInterface          $entityManager,
                        SandwichRepository              $sandwichRepo,
                        BoissonRepository               $boissonRepo,
                        DessertRepository               $dessertRepo,
                        DesactivationCommandeRepository $deactiveRepo,
                        LimitationCommandeRepository    $limiteRepo,
                        UserRepository                  $userRepository): Response
    {
        $user = $userRepository->find($this->getUser());
        $roles = $user->getRoles();
        $dateNow = new DateTime('now', new DateTimeZone('Europe/Paris'));
        $deactive = $deactiveRepo->findOneBy(['id' => 1]);
        $sandwichs = $sandwichRepo->findByDispo(true);
        $boisson = $boissonRepo->findOneByNom('Eau');
        $desserts = $dessertRepo->findByDispo(true);
        $limiteDate = $limiteRepo->findOneById(5);
        $commandeGroupe = new CommandeGroupe();
        if ($limiteDate->getIsActive() && (!in_array("ROLE_ADMIN", $roles) && !in_array("ROLE_CUISINE", $roles))) {
            $form = $this->createForm(CommandeGroupeType::class,
                $commandeGroupe, ['limiteDateSortie' => $limiteDate->getNbLimite(),
                    'sandwichChoisi1' => null, 'sandwichChoisi2' => null]);
        } else {
            $form = $this->createForm(CommandeGroupeType::class, $commandeGroupe,
                ['sandwichChoisi1' => null, 'sandwichChoisi2' => null]);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sandwich1 = $form->get('sandwichChoisi1')->getData();
            $sandwich2 = $form->get('sandwichChoisi2')->getData();

            if ($sandwich1 != $sandwich2) {
                $commandeur = $form->get('commandeur')->getData();

                if ($commandeur) {
                    $commandeGroupe->setCommandeur($commandeur);
                } else {
                    $commandeGroupe->setCommandeur($user);
                }

                $commandeGroupe
                    ->setDateCreation($dateNow)
                    ->setBoissonChoisie($boisson)
                    ->setEstValide(true);
                $entityManager->persist($commandeGroupe);
                $entityManager->flush();

                $sandwichsChoisi = [
                    $sandwich1,
                    $sandwich2
                ];
                $nbSandwich = [
                    $form->get('nbSandwichChoisi1')->getData(),
                    $form->get('nbSandwichChoisi2')->getData()
                ];

                $i = 0;
                foreach ($sandwichsChoisi as $sandwichChoisi) {
                    $groupeSandwich = new SandwichCommandeGroupe();
                    $groupeSandwich
                        ->setCommandeAffecte($commandeGroupe)
                        ->setSandwichChoisi($sandwichChoisi)
                        ->setNombreSandwich($nbSandwich[$i]);
                    $entityManager->persist($groupeSandwich);
                    $entityManager->flush();
                    $i++;
                }
                $this->addFlash(
                    'SuccessComGr',
                    'Votre commande groupé a été sauvegardée !'
                );
            } else {
                $this->addFlash(
                    'FailedComGr',
                    'Vous ne pouvez pas choisir le même sandwich !'
                );
            }

            return $this->redirectToRoute('commande_groupe_new',
                [], Response::HTTP_SEE_OTHER);
        }

        if ($deactive->getIsDeactivated() === true) {
            return $this->redirectToRoute('deactivate_commande');
        } else {
            return $this->renderForm('commande_groupe/new.html.twig', [
                'commande_groupe' => $commandeGroupe,
                'form' => $form,
                'sandwichs' => $sandwichs,
                'desserts' => $desserts,
            ]);
        }
    }

    /**
     * @Route("/{id}/delete_view", name="commande_groupe_delete_view", methods={"GET", "POST"})
     */
    public function delete_view(CommandeGroupe $commandeGroupe): Response
    {
        return $this->render('commande_groupe/delete_view.html.twig', [
            'commande_groupe' => $commandeGroupe,
        ]);
    }

    /**
     * @Route("/validate/{id}", name="validate_commande_groupe",methods={"GET","POST"})
     */
    public function validateCommande(CommandeGroupe $commande, EntityManagerInterface $entityManager): RedirectResponse
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
     * @Route("/{id}/edit", name="commande_groupe_edit", methods={"GET", "POST"})
     */
    public function edit(Request                          $request,
                         EntityManagerInterface           $entityManager,
                         SandwichRepository               $sandwichRepo,
                         DessertRepository                $dessertRepo,
                         DesactivationCommandeRepository  $deactiveRepo,
                         CommandeGroupe                   $commandeGroupe,
                         SandwichCommandeGroupeRepository $sandComRepo,
                         UserRepository                   $userRepository): Response
    {
        $deactive = $deactiveRepo->findOneBy(['id' => 1]);
        $sandwichs = $sandwichRepo->findByDispo(true);
        $desserts = $dessertRepo->findByDispo(true);
        $groupeSandwich = $sandComRepo->findBy(['commandeAffecte' => $commandeGroupe->getId()]);
        $form = $this->createForm(CommandeGroupeType::class, $commandeGroupe, ['limiteDateSortie' => 0
            , 'sandwichChoisi1' => $groupeSandwich[0], 'sandwichChoisi2' => $groupeSandwich[1]]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sandwich1 = $form->get('sandwichChoisi1')->getData();
            $sandwich2 = $form->get('sandwichChoisi2')->getData();
            if ($sandwich1 != $sandwich2) {
                $commandeur = $form->get('commandeur')->getData();

                if ($commandeur) {
                    $commandeGroupe->setCommandeur($commandeur);
                } else {
                    $commandeGroupe->setCommandeur($userRepository->find($this->getUser()));
                }
                $entityManager->flush();

                $sandwichsChoisi = [
                    $sandwich1,
                    $sandwich2
                ];
                $nbSandwich = [
                    $form->get('nbSandwichChoisi1')->getData(),
                    $form->get('nbSandwichChoisi2')->getData()
                ];
                $i = 0;

                foreach ($sandwichsChoisi as $sandwichChoisi) {
                    $groupeSandwich[$i]
                        ->setCommandeAffecte($commandeGroupe)
                        ->setSandwichChoisi($sandwichChoisi)
                        ->setNombreSandwich($nbSandwich[$i]);
                    $entityManager->flush();
                    $i++;
                }

                $this->addFlash(
                    'SuccessComGr',
                    'Votre commande groupée a été modifiée !'
                );
            } else {
                $this->addFlash(
                    'FailedComGr',
                    'Vous ne pouvez pas choisir le même sandwich !'
                );
            }

            return $this->redirectToRoute('commande_groupe_edit',
                ['id' => $commandeGroupe->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($deactive->getIsDeactivated() === true) {
            return $this->redirectToRoute('deactivate_commande');
        } else {
            return $this->renderForm('commande_groupe/edit.html.twig', [
                'commande_groupe' => $commandeGroupe,
                'form' => $form,
                'sandwichs' => $sandwichs,
                'desserts' => $desserts,
            ]);
        }
    }

    /**
     * @Route("/{id}", name="commande_groupe_delete", methods={"POST"})
     */
    public function delete(Request                $request,
                           CommandeGroupe         $commandeGroupe,
                           EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commandeGroupe->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commandeGroupe);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteComGr',
                'La commande groupée a été annulée !'
            );
        }

        return $this->redirectToRoute('commande_individuelle_index', [], Response::HTTP_SEE_OTHER);
    }
}
