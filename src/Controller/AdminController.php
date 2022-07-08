<?php

namespace App\Controller;

use App\Repository\DesactivationCommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * Espace administration
     * @Route("/admin", name="espace_admin")
     * @param DesactivationCommandeRepository $repository Utilisé pour la désactivation du service de commande de sandwich
     * @return Response
     */
    public function index(DesactivationCommandeRepository $repository): Response
    {
        return $this->render('admin/admin.html.twig', [
            'desactivation' => $repository->findOneBy(['id' => 1]),
        ]);
    }
}
