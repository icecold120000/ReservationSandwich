<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\OubliMdpType;
use App\Form\UserMdpType;
use App\Repository\UserRepository;
use App\Security\LoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class OubliMdpController extends AbstractController
{
    /**
     * Formulaire d'oubli de mot de passe
     * @Route("/oubli/mdp", name="oubli_mdp")
     * @param Request $request
     * @param MailerInterface $mailer
     * @param UserRepository $userRepo
     * @return Response
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     */
    public function forgottenPassword(Request         $request,
                                      MailerInterface $mailer,
                                      UserRepository  $userRepo): Response
    {
        $form = $this->createForm(OubliMdpType::class);
        $email = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userEmail = $email->get("emailFirst")->getData();
            $dateNaissance = $email->get("dateAnniversaire")->getData();
            $user = $userRepo->findOneByEmailAndDate($userEmail, $dateNaissance);
            /*Vérifie si l'utilisateur existe et renvoie un message d'erreur*/
            if (empty($user))
                $error = "L'adresse mail n'est lié à aucun compte !";
            else {
                $email = (new TemplatedEmail())
                    ->from('cuisine.saintvincentsenlis@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Votre réinitialisation de mot de passe')
                    ->htmlTemplate('email/send_oubli_mdp.html.twig')
                    ->context([
                        'user' => $user
                    ]);
                /*Envoie d'un email*/
                $mailer->send($email);
                $this->addFlash(
                    'SuccessOubli',
                    'Votre demande a été envoyée.
                    Vous allez recevoir un email vous permettant de réinitialiser votre mot de passe.'
                );

                return $this->redirectToRoute("oubli_mdp");
            }
        }

        return $this->render('oubli_mdp/index.html.twig', [
            'error' => $error ?? null,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de réinitialisation de mot de passe
     * @Route("/oubli/mdp/{userTokenHash}", name="oubli_mdp_reset", methods={"GET","POST"})
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     * @param EntityManagerInterface $em
     * @param User $user
     * @param UserRepository $userRepo
     * @param Request $request
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param UserAuthenticatorInterface $userAuthenticator
     * @param LoginAuthenticator $authenticator
     * @return Response|null
     * @throws NonUniqueResultException
     */
    public function resetPassword(EntityManagerInterface      $em,
                                  User                        $user,
                                  UserRepository              $userRepo,
                                  Request                     $request,
                                  UserPasswordHasherInterface $userPasswordHasher,
                                  UserAuthenticatorInterface  $userAuthenticator,
                                  LoginAuthenticator          $authenticator): ?Response
    {
        $form = $this->createForm(UserMdpType::class);
        $form->handleRequest($request);
        $userFound = $userRepo->findOneByEmail($user->getEmail());

        if ($form->isSubmitted() && $form->isValid()) {
            /*Hash le nouveau mot de passe*/
            if ($form->get('plainPassword')->getData()) {
                $userFound->setPassword(
                    $userPasswordHasher->hashPassword(
                        $userFound,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            $em->flush();
            $this->addFlash(
                'SuccessResetMdp',
                'Votre mot de passe a été modifié !'
            );
            /*Authentifie l'utilisateur*/
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('oubli_mdp/form_mdp.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
