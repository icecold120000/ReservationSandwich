<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\EleveRepository;
use App\Repository\InscriptionCantineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    /**
     * Formulaire de modification de profil
     * @Route("profile/{userTokenHash}/edit", name="profile_edit", methods={"GET","POST"})
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     * @param Request $request
     * @param User $user
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function edit(Request                     $request,
                         User                        $user,
                         UserPasswordHasherInterface $userPasswordHasher,
                         EntityManagerInterface      $em): Response
    {
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Hash le nouveau mot de passe*/
            if ($form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }
            /*Regénére un nouveau token hash*/
            $user->setTokenHash(md5($user->getId() . $user->getEmail()));
            $em->flush();

            $this->addFlash(
                'SuccessProfile',
                'Votre profil a été modifié !'
            );
            return $this->redirectToRoute('profile_edit', array('id' => $user->getId()));
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page de profil d'un administrateur
     * @Route("/profile/admin/{userTokenHash}", name="profile_admin")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     */
    public function admin(): Response
    {
        return $this->render('profile/admin.html.twig');
    }

    /**
     * Page de profil d'un élève
     * @Route("/profile/eleve/{userTokenHash}", name="profile_eleve")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     * @param EleveRepository $eleveRepository
     * @param User $user
     * @param InscriptionCantineRepository $cantineRepository
     * @return Response
     * @throws NonUniqueResultException
     */
    public function eleve(EleveRepository              $eleveRepository,
                          User                         $user,
                          InscriptionCantineRepository $cantineRepository): Response
    {
        /*Récupére l'élève et les inscriptions à la cantine de l'élève*/
        $eleveFound = $eleveRepository->findByNomPrenomDateNaissance($user->getNomUser(),
            $user->getPrenomUser(), $user->getDateNaissanceUser());
        $inscrit = $cantineRepository->findOneByEleve($eleveFound->getId());

        return $this->render('profile/eleve.html.twig', [
            'eleve' => $eleveFound,
            'inscritCantine' => $inscrit,
        ]);
    }

    /**
     * Page de profil d'un personnel de cuisine
     * @Route("/profile/cuisine/{userTokenHash}", name="profile_cuisine")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     */
    public function cuisine(): Response
    {
        return $this->render('profile/cuisine.html.twig');
    }

    /**
     * Page de profil d'un adulte
     * @Route("/profile/adulte/{userTokenHash}", name="profile_adulte")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     */
    public function adulte(): Response
    {
        return $this->render('profile/adulte.html.twig');
    }

    /**
     * Page de profil d'un utilisateur connecté
     * @Route("/profile/user/{userTokenHash}", name="profile_user")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     */
    public function user(): Response
    {
        return $this->render('profile/user.html.twig');
    }
}
