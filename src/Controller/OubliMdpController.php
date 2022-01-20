<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OubliMdpController extends AbstractController
{
    /**
     * @Route("/oubli/mdp", name="oubli_mdp")
     */
    public function index(): Response
    {
        return $this->render('oubli_mdp/index.html.twig', [
            'controller_name' => 'OubliMdpController',
        ]);
    }
}
