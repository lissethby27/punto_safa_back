<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontendController extends AbstractController
{
    #[Route('/{route}', name: 'angular_routes', requirements: ['route' => '^(?!api).*$'], methods: ['GET'])]
    public function index(): Response
    {
        return $this->file($this->getParameter('kernel.project_dir') . '/public/theme/index.html');
    }
}
