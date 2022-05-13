<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\AdulteRepository;
use App\Repository\EleveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     * @throws Exception
     */
    public function register(Request                     $request,
                             UserPasswordHasherInterface $userPasswordHasher,
                             EntityManagerInterface      $entityManager,
                             EleveRepository             $eleveRepository,
                             AdulteRepository            $adulteRepository): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eleveFound = $eleveRepository->findByNomPrenomDateNaissance($form->get('nomUser')->getData(),
                $form->get('prenomUser')->getData(), $form->get('dateNaissanceUser')->getData());
            $adulteFound = $adulteRepository->findByNomPrenomDateNaissance($form->get('nomUser')->getData(),
                $form->get('prenomUser')->getData(), $form->get('dateNaissanceUser')->getData());

            if ($eleveFound || $adulteFound) {
                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
                $user->setTokenHash(md5($user->getNomUser() . $user->getEmail()));
                $user->setIsVerified(true);

                if ($eleveFound) {
                    $user->setRoles([User::ROLE_ELEVE]);
                } elseif ($adulteFound) {
                    $user->setRoles([User::ROLE_ADULTES]);
                } else {
                    $user->setRoles([User::ROLE_USER]);
                }

                $entityManager->persist($user);
                $entityManager->flush();

                if ($eleveFound) {
                    $eleveFound->setCompteEleve($user);
                } elseif ($adulteFound) {
                    $adulteFound->setCompteAdulte($user);
                }

                $entityManager->flush();

                $this->addFlash(
                    'successInscription',
                    'Votre inscription a été validée. Vous pouvez vous connecter.'
                );

                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash(
                    'failedInscription',
                    'Votre demande d\'inscription a été refusée.
                Vous n\'êtes pas reconnu en tant qu\'élève ou personnel de l\'établissement.
                Merci de vérifier que vos données
                 (nom, prénom et date de naissance) correspondent à celles données à l\'administration.'
                );
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
