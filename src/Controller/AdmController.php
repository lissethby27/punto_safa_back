<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Repository\ClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin')]
class AdmController extends AbstractController
{
    private $serializer;

    #[Route('/auth/user', name: 'admin_auth_user', methods: ['GET'])]
    public function getAuthenticatedAdmin(SerializerInterface $serializer): JsonResponse
    {
        // Obtener el usuario autenticado
        $usuario = $this->getUser();

        if (!$usuario instanceof UserInterface) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar que el usuario tenga el rol de admin
        if (!in_array('ROLE_ADMIN', $usuario->getRoles(), true)) {
            return new JsonResponse(['error' => 'El usuario no tiene permisos de administrador'], Response::HTTP_FORBIDDEN);
        }

        // Serializar la información del administrador
        $json = $serializer->serialize($usuario, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'by_id', methods: ['GET'])]
    public function getById(Cliente $cliente, EntityManagerInterface $entityManager): JsonResponse
    {
        $usuario = $cliente->getUsuario();

        if ($usuario) {
            $entityManager->initializeObject($usuario);
        }

        if ($usuario) {
            $rol = $usuario->getRol();
        } else {
            return $this->json(['error' => 'El cliente no tiene usuario asociado'], 404);
        }

        $json = $this->serializer->serialize($cliente, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, 200, [], true);
    }


}
