<?php

namespace App\Controller;

use App\Repository\DesactivationCommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="espace_admin")
     */
    public function index(DesactivationCommandeRepository $repository): Response
    {
        return $this->render('admin/admin.html.twig',[
            'desactivation' => $repository->findOneBy(['id' => 1]),
        ]);
    }
}
