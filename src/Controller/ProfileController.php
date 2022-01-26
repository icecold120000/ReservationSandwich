<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profile", name="profile_admin")
     */
    public function admin(): Response
    {
        return $this->render('profile/admin.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }

    /**
     * @Route("/profile", name="profile_eleve")
     */
    public function eleve(): Response
    {
        return $this->render('profile/eleve.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }

    /**
     * @Route("/profile", name="profile_cuisine")
     */
    public function cuisine(): Response
    {
        return $this->render('profile/cuisine.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }

    /**
     * @Route("/profile", name="profile_adulte")
     */
    public function adulte(): Response
    {
        return $this->render('profile/adulte.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }
}
