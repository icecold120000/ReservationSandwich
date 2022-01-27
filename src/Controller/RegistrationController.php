<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request,
                             UserPasswordHasherInterface $userPasswordHasher,
                             EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
            $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setTokenHash(md5($user->getId().$user->getEmail()));

            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash(
                'successInscription',
                'Votre inscription a été validé.
                 Vous pouvez vous connecter en revenant sur la page de connexion.'
            );

            return $this->redirectToRoute('homepage');
        }
        elseif($form->isSubmitted() && !$form->isValid())
        {
            $this->addFlash(
                'failedInscription',
                'Votre demande d\'inscription a été refusée.
                    Vous n\'êtes pas reconnu en tant qu\'élève de l\'établissement.
                    Merci de vérifier que vos données
                     (nom, prénom et date de naissance) correspondent à celles données à l\'établissement.'
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
