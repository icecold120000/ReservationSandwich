<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\OubliMdpType;
use App\Form\UserMdpType;
use App\Repository\UserRepository;
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
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class OubliMdpController extends AbstractController
{
    /**
     * @Route("/oubli/mdp", name="oubli_mdp")
     * @throws TransportExceptionInterface
     * @throws NonUniqueResultException
     * Formulaire d'oubli de mot de passe
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
                /*Envoie d'un email*/
                $email = (new TemplatedEmail())
                    ->from('cuisine.saintvincentsenlis@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Votre réinitialisation de mot de passe')
                    ->htmlTemplate('email/send_oubli_mdp.html.twig')
                    ->context([
                        'user' => $user
                    ]);

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
     * @Route("/oubli/mdp/{userTokenHash}", name="oubli_mdp_reset", methods={"GET","POST"})
     * @Entity("user", expr="repository.findOneByToken(userTokenHash)")
     * @throws NonUniqueResultException
     * Formulaire de réinitialisation de mot de passe
     */
    public function resetPassword(EntityManagerInterface      $em,
                                  User                        $user,
                                  UserRepository              $userRepo,
                                  Request                     $request,
                                  UserPasswordHasherInterface $userPasswordHasher): ?Response
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
            new Passport(
                new UserBadge($userFound->getEmail()),
                new PasswordCredentials($request->request->get('plainPassword', '')),
                [
                    new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                ]
            );

            return $this->redirectToRoute('homepage');
        }

        return $this->render('oubli_mdp/form_mdp.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
