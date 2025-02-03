<?php

namespace App\Controller\api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController extends AbstractController{
    #[Route('/api/test', name: 'app_api')]
    public function testConnection(): JsonResponse
    {
        return $this->json(['message' => 'Backend is connected successfully!']);
    }
}
