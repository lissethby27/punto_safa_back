<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController extends AbstractController
{
    #[Route('/api/saludo', name: 'api_saludo', methods: ['GET'])]
    public function saludo(): JsonResponse
    {
        return $this->json(['mensaje' => '¡Hola Mundo!']);

    }
}
