<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\AdulteRepository;
use App\Repository\EleveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     * @throws \Exception
     */
    public function register(Request $request,
                             UserPasswordHasherInterface $userPasswordHasher,
                             EntityManagerInterface $entityManager,
                             EleveRepository $eleveRepository,
                             AdulteRepository $adulteRepository,
                             RateLimiterFactory $anonymousApiLimiter): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        $limiter = $anonymousApiLimiter->create($request->getClientIp());

        // the argument of consume() is the number of tokens to consume
        // and returns an object of type Limit
        if (false === $limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException('Dans une heure','Vous avez fait trop de demande d\'inscription !');
        }
        else {
            if ($form->isSubmitted() && $form->isValid()) {

                $eleveFound = $eleveRepository->findByNomPrenomDateNaissance($form->get('nomUser')->getData(),
                    $form->get('prenomUser')->getData(), $form->get('dateNaissanceUser')->getData());


                $adulteFound = $adulteRepository->findByNomPrenomDateNaissance($form->get('nomUser')->getData(),
                    $form->get('prenomUser')->getData(), $form->get('dateNaissanceUser')->getData());

                if ($eleveFound != false || $adulteFound != false){
                    // encode the plain password
                    $user->setPassword(
                        $userPasswordHasher->hashPassword(
                            $user,
                            $form->get('plainPassword')->getData()
                        )
                    );
                    $user->setTokenHash(md5($user->getNomUser().$user->getEmail()));
                    $user->setIsVerified(true);

                    if ($eleveFound) {
                        $user->setRoles([User::ROLE_ELEVE]);
                    }
                    elseif ($adulteFound) {
                        $user->setRoles([User::ROLE_ADULTES]);
                    }
                    else {
                        $user->setRoles([User::ROLE_USER]);
                    }

                    $entityManager->persist($user);
                    $entityManager->flush();
                    $this->addFlash(
                        'successInscription',
                        'Votre inscription a été validé. Vous pouvez vous connecter.'
                    );

                    return $this->redirectToRoute('app_login');
                } else
                {
                    $this->addFlash(
                        'failedInscription',
                        'Votre demande d\'inscription a été refusée.
                    Vous n\'êtes pas reconnu en tant qu\'élève ou personnel de l\'établissement.
                    Merci de vérifier que vos données
                     (nom, prénom et date de naissance) correspondent à celles données à l\'administration.'
                    );
                }

            }
        }


        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
