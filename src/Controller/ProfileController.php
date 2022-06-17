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
     * @Route("profile/{id}/edit", name="profile_edit", methods={"GET","POST"})
     */
    public function edit(Request                     $request,
                         User                        $user,
                         UserPasswordHasherInterface $userPasswordHasher,
                         EntityManagerInterface      $em): Response
    {
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

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
     * @Route("/profile/admin/{userTokenHash}", name="profile_admin")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     */
    public function admin(): Response
    {
        return $this->render('profile/admin.html.twig');
    }

    /**
     * @Route("/profile/eleve/{userTokenHash}", name="profile_eleve")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     * @throws NonUniqueResultException
     */
    public function eleve(EleveRepository              $eleveRepository,
                          User                         $user,
                          InscriptionCantineRepository $cantineRepository): Response
    {
        $eleveFound = $eleveRepository->findByNomPrenomDateNaissance($user->getNomUser(),
            $user->getPrenomUser(), $user->getDateNaissanceUser());
        $inscrit = $cantineRepository->findOneByEleve($eleveFound->getId());

        return $this->render('profile/eleve.html.twig', [
            'eleve' => $eleveFound,
            'inscritCantine' => $inscrit,
        ]);
    }

    /**
     * @Route("/profile/cuisine/{userTokenHash}", name="profile_cuisine")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     */
    public function cuisine(): Response
    {
        return $this->render('profile/cuisine.html.twig');
    }

    /**
     * @Route("/profile/adulte/{userTokenHash}", name="profile_adulte")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     */
    public function adulte(): Response
    {
        return $this->render('profile/adulte.html.twig');
    }

    /**
     * @Route("/profile/user/{userTokenHash}", name="profile_user")
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     */
    public function user(): Response
    {
        return $this->render('profile/user.html.twig');
    }
}
