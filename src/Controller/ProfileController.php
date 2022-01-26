<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{

    /**
     * @Route("profile/{id}/edit", name="profile_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user,
                         UserPasswordHasherInterface $userPasswordHasher,
                         EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if($form->get('plainPassword')->getData())
            {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            $user->setTokenHash(md5($user->getId().$user->getEmail()));

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
     * @Route("/profile/admin", name="profile_admin")
     */
    public function admin(): Response
    {
        return $this->render('profile/admin.html.twig', [

        ]);
    }

    /**
     * @Route("/profile/eleve", name="profile_eleve")
     */
    public function eleve(): Response
    {
        return $this->render('profile/eleve.html.twig', [

        ]);
    }

    /**
     * @Route("/profile/cuisine", name="profile_cuisine")
     */
    public function cuisine(): Response
    {
        return $this->render('profile/cuisine.html.twig', [

        ]);
    }

    /**
     * @Route("/profile/adulte", name="profile_adulte")
     */
    public function adulte(): Response
    {
        return $this->render('profile/adulte.html.twig', [

        ]);
    }
}
