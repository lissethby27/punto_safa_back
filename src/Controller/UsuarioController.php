<?php

namespace App\Controller;

use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UsuarioController extends AbstractController
{
    #[Route('/api/registro', name: 'app_usuario', methods: ['POST'])]
    public function registro(Request $request,UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $body = json_decode($request -> getContent(), true);

        $nuevo_usuario = new Usuario();
        $nuevo_usuario -> setNick($body['nick']);
        $nuevo_usuario->setEmail($body['email']);
        $nuevo_usuario -> setContrasena($userPasswordHasher -> hashPassword($nuevo_usuario, $body['contrasena']));
        $nuevo_usuario -> setRol("cliente");

        $entityManager->persist($nuevo_usuario);
        $entityManager->flush();

        /** @var Usuario $usuario */
        // $usuario = $this->getUser();

        return new JsonResponse(['mensaje' => 'Usuario registrado correctamente'], 201);

    }
}
