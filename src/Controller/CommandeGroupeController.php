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
     * Formulaire d'ajout d'une commande groupée
     * @Route("/new", name="commande_groupe_new", methods={"GET", "POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SandwichRepository $sandwichRepo
     * @param BoissonRepository $boissonRepo
     * @param DessertRepository $dessertRepo
     * @param DesactivationCommandeRepository $deactiveRepo
     * @param LimitationCommandeRepository $limiteRepo
     * @param UserRepository $userRepository
     * @return Response
     * @throws NonUniqueResultException
     * @throws Exception
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
        /*Récupèration de l'utilisateur courant et son rôle*/
        $user = $userRepository->find($this->getUser());
        $roles = $user->getRoles();

        /*Récupération de la date d'ajourd'hui*/
        $dateNow = new DateTime('now', new DateTimeZone('Europe/Paris'));

        /*Récupération de la donnée permettant de désactiver ou non le service de commandes*/
        $deactive = $deactiveRepo->findOneBy(['id' => 1]);

        /*Récupération des produits disponibles*/
        $sandwichs = $sandwichRepo->findByDispo(true);
        $boisson = $boissonRepo->findOneByNom('Eau');
        $desserts = $dessertRepo->findByDispo(true);

        /*Récupération de la limite de 7 jours avant les commandes pour les sorties*/
        $limiteDate = $limiteRepo->findOneById(5);
        $commandeGroupe = new CommandeGroupe();

        /*Vérifie si la limite est active et que l'utilisateur n'est un administrateur ou
        un personnel de cuisine
        si oui la limitation est mise en place pour l'utilisateur dans le formulaire
        sinon la limitation n'est pas en place et certains champs ne sont pas obligatoire pour
        administrateur et personnel de cuisine
        */
        if ($limiteDate->getIsActive() && (!in_array("ROLE_ADMIN", $roles) && !in_array("ROLE_CUISINE", $roles))) {
            $form = $this->createForm(CommandeGroupeType::class,
                $commandeGroupe, ['limiteDateSortie' => $limiteDate->getNbLimite(),
                    'sandwichChoisi1' => null, 'sandwichChoisi2' => null]);
        } else {
            $form = $this->createForm(CommandeGroupeType::class, $commandeGroupe,
                ['sandwichChoisi1' => null, 'sandwichChoisi2' => null, 'requiredNonAdmin' => false]);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Récupère les sandwichs commandés*/
            $sandwich1 = $form->get('sandwichChoisi1')->getData();
            $sandwich2 = $form->get('sandwichChoisi2')->getData();

            /*Vérifie si les sandwichs commandes sont différents
             si oui, la commande groupée est réalisé
             sinon un message d'erreur est affiché
            */
            if ($sandwich1 != $sandwich2) {
                $commandeur = $form->get('commandeur')->getData();
                /*Si le champ personne qui a commandé ce sandwich n'est
                 pas null et affecte cette commande
                */
                if ($commandeur) {
                    $commandeGroupe->setCommandeur($commandeur);
                } else {
                    $commandeGroupe->setCommandeur($user);
                }

                /*Vérifie si l'utilisateur est un administrateur ou un personnel de cuisine
                 et qu'un des champs non requis par cet utilisateur n'est pas rempli*/
                if ((in_array("ROLE_ADMIN", $roles) || in_array("ROLE_CUISINE", $roles))
                    && ($form->get('motifSortie')->getData() === null
                        || $form->get('commentaireCommande')->getData()) === null) {
                    if ($form->get('motifSortie')->getData() != null) {
                        $commandeGroupe->setMotifSortie($form->get('motifSortie')->getData());
                    }
                    if ($form->get('commentaireCommande')->getData() != null) {
                        $commandeGroupe->setCommentaireCommande($form->get('commentaireCommande')->getData());
                    }
                }

                $commandeGroupe
                    ->setDateCreation($dateNow)
                    ->setBoissonChoisie($boisson)
                    ->setEstValide(true);
                $entityManager->persist($commandeGroupe);
                $entityManager->flush();

                /*Récupère les sandwichs choisis et
                 leur nombre commandé sous forme de tableau*/
                $sandwichsChoisi = [
                    $sandwich1,
                    $sandwich2
                ];
                $nbSandwich = [
                    $form->get('nbSandwichChoisi1')->getData(),
                    $form->get('nbSandwichChoisi2')->getData()
                ];

                $i = 0;
                /*Pour chaque sandwich choisi, il crée un sandwich commande groupe et
                 affecte la commande groupée réalisée ci-dessus
                */
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
        /*Vérifie si le service de restauration est désactivé, il retourne sur la page
         de désactivation de service sinon il retourne le formulaire
         d'ajout d'une commande groupée
        */
        if ($deactive->getIsDeactivated() === true) {
            return $this->redirectToRoute('deactivate_commande');
        } else {
            return $this->render('commande_groupe/new.html.twig', [
                'commande_groupe' => $commandeGroupe,
                'form' => $form->createView(),
                'sandwichs' => $sandwichs,
                'desserts' => $desserts,
            ]);
        }
    }

    /**
     * Page de pré-suppression d'une commande groupée
     * @Route("/{id}/delete_view", name="commande_groupe_delete_view", methods={"GET", "POST"})
     * @param CommandeGroupe $commandeGroupe
     * @return Response
     */
    public function delete_view(CommandeGroupe $commandeGroupe): Response
    {
        return $this->render('commande_groupe/delete_view.html.twig', [
            'commande_groupe' => $commandeGroupe,
        ]);
    }

    /**
     * Fonction permettant de valider ou invalider une commande groupée
     * @Route("/validate/{id}", name="validate_commande_groupe",methods={"GET","POST"})
     */
    public function validateCommande(CommandeGroupe $commande, EntityManagerInterface $entityManager): RedirectResponse
    {
        /*Récupère le champ est valide de commande groupée et vérifie si
         elle est valide ou non et le change à son contraire
        */
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
     * Formulaire de modification d'une commande groupée
     * @Route("/{id}/edit", name="commande_groupe_edit", methods={"GET", "POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SandwichRepository $sandwichRepo
     * @param DessertRepository $dessertRepo
     * @param DesactivationCommandeRepository $deactiveRepo
     * @param CommandeGroupe $commandeGroupe
     * @param SandwichCommandeGroupeRepository $sandComRepo
     * @param UserRepository $userRepository
     * @return Response
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
        /*Récupération de la donnée qui désactive ou non le service de commande*/
        $deactive = $deactiveRepo->findOneBy(['id' => 1]);

        /*Récupère les sandwichs et desserts disponibles*/
        $sandwichs = $sandwichRepo->findByDispo(true);
        $desserts = $dessertRepo->findByDispo(true);

        /*Récupère les sandwichs choisis et les affectent dans le formulaire de modfication*/
        $groupeSandwich = $sandComRepo->findBy(['commandeAffecte' => $commandeGroupe->getId()]);
        $form = $this->createForm(CommandeGroupeType::class, $commandeGroupe, ['limiteDateSortie' => 0
            , 'sandwichChoisi1' => $groupeSandwich[0], 'sandwichChoisi2' => $groupeSandwich[1]]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /*Récupère les sandwichs du formulaire*/
            $sandwich1 = $form->get('sandwichChoisi1')->getData();
            $sandwich2 = $form->get('sandwichChoisi2')->getData();

            /*Vérifie si les sandwichs sont différents*/
            if ($sandwich1 != $sandwich2) {
                $commandeur = $form->get('commandeur')->getData();

                /*Vérifie si le champ commandeur est rempli*/
                if ($commandeur) {
                    $commandeGroupe->setCommandeur($commandeur);
                } else {
                    $commandeGroupe->setCommandeur($userRepository->find($this->getUser()));
                }
                $entityManager->flush();

                /*Récupère les sandwichs choisis et leur nombre commandé */
                $sandwichsChoisi = [
                    $sandwich1,
                    $sandwich2
                ];
                $nbSandwich = [
                    $form->get('nbSandwichChoisi1')->getData(),
                    $form->get('nbSandwichChoisi2')->getData()
                ];
                $i = 0;

                /*Modifie les sandwichs affectés à cette commande groupée*/
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
        /*Vérifie si le service de commande est désactivé, il retourne la page de désactivation
         de service sinon il retourne le formulaire de modification de la commande groupée
         */
        if ($deactive->getIsDeactivated() === true) {
            return $this->redirectToRoute('deactivate_commande');
        } else {
            return $this->render('commande_groupe/edit.html.twig', [
                'commande_groupe' => $commandeGroupe,
                'form' => $form->createView(),
                'sandwichs' => $sandwichs,
                'desserts' => $desserts,
            ]);
        }
    }

    /**
     * Page de pré-suppression d'une commande groupée
     * @Route("/{id}", name="commande_groupe_delete", methods={"POST"})
     * @param Request $request
     * @param CommandeGroupe $commandeGroupe
     * @param EntityManagerInterface $entityManager
     * @param SandwichCommandeGroupeRepository $sandComRepo
     * @return Response
     */
    public function delete(Request                          $request,
                           CommandeGroupe                   $commandeGroupe,
                           EntityManagerInterface           $entityManager,
                           SandwichCommandeGroupeRepository $sandComRepo): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commandeGroupe->getId(), $request->request->get('_token'))) {

            /*Récupère les sandwichs affectés à la commande groupée et les suppriment*/
            $groupeSandwich = $sandComRepo->findBy(['commandeAffecte' => $commandeGroupe->getId()]);
            foreach ($groupeSandwich as $sandwich) {
                $entityManager->remove($sandwich);
            }
            $entityManager->remove($commandeGroupe);
            $entityManager->flush();

            /*Message de validation*/
            $this->addFlash(
                'SuccessDeleteComGr',
                'La commande groupée a été annulée !'
            );
        }

        return $this->redirectToRoute('commande_individuelle_index', [], Response::HTTP_SEE_OTHER);
    }
}
