<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api')]
final class UsuarioController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }


    #[Route('/registro', name: 'app_usuario', methods: ['POST'])]
    public function registro(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $body = json_decode($request->getContent(), true);


        // Crear usuario
        $nuevo_usuario = new Usuario();
        $nuevo_usuario->setNick($body['nick']);
        $nuevo_usuario->setEmail($body['email']);
        $nuevo_usuario->setContrasena($userPasswordHasher->hashPassword($nuevo_usuario, $body['contrasena']));
        $nuevo_usuario->setRol("cliente");

        $entityManager->persist($nuevo_usuario);
        $entityManager->flush(); // Guardar usuario primero

        // Crear cliente asociado
        $nuevo_cliente = new Cliente();
        $nuevo_cliente->setNombre($body['nombre']);
        $nuevo_cliente->setApellidos($body['apellidos']);
        $nuevo_cliente->setDNI($body['dni']);
        $nuevo_cliente->setFoto($body['foto']);
        $nuevo_cliente->setDireccion($body['direccion']);
        $nuevo_cliente->setTelefono($body['telefono']);
        $nuevo_cliente->setUsuario($nuevo_usuario); // Asignar el usuario

        // Si necesitas acceder a la propiedad 'rol' del usuario, inicializa el objeto 'Usuario'
        $usuario = $nuevo_cliente->getUsuario();
        if ($usuario instanceof \Doctrine\ORM\Proxy\Proxy) {
            $entityManager->initializeObject($usuario);
        }

        // Ahora puedes acceder a la propiedad 'rol' sin problemas
        $rol = $usuario->getRol();

        $entityManager->persist($nuevo_cliente);
        $entityManager->flush(); // Guardar cliente

        return new JsonResponse(['mensaje' => 'Usuario y Cliente registrados correctamente'], 201);
    }



    #[Route('usuario/all', name: 'all', methods: ['GET'])]
    public function getUsuarios(UsuarioRepository $usuarioRepository, SerializerInterface $serializer): JsonResponse
    {
        $usuarios = $usuarioRepository->findAll();

        $json = $serializer->serialize($usuarios, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
        ]);

        return new JsonResponse($json, 200, [], true);
    }


    #[Route('/usuario/editar/{id}', name: 'usuario_editar', methods: ['PUT'])]
    public function editar(int $id, Request $request, EntityManagerInterface $entityManager, UsuarioRepository $usuarioRepository): JsonResponse
    {
        // Obtén los datos del usuario desde el cuerpo de la solicitud
        $json_usuario = json_decode($request->getContent(), true);

        // Busca el usuario en la base de datos
        $usuario = $usuarioRepository->find($id);

        if (!$usuario) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Verifica que todos los datos requeridos estén presentes
        if (!isset($json_usuario['nick'], $json_usuario['contrasena'], $json_usuario['rol'], $json_usuario['email'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        // Actualiza los datos del usuario
        $usuario->setNick($json_usuario['nick']);
        $usuario->setContrasena($json_usuario['contrasena']);
        $usuario->setRol($json_usuario['rol']);
        $usuario->setEmail($json_usuario['email']);

        // Guarda los cambios en la base de datos
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos actualizados correctamente']);
    }

    #[Route('/usuario/{id}', name: 'usuario_delete_by_id', methods: ['DELETE'])]
    public function deleteById(int $id, UsuarioRepository $usuarioRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // Buscar el usuario en la base de datos
        $usuario = $usuarioRepository->find($id);

        // Si el usuario no se encuentra, devolver un error 404
        if (!$usuario) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Eliminar el usuario
        $entityManager->remove($usuario);
        $entityManager->flush();

        // Devolver una respuesta con éxito
        return $this->json(['mensaje' => 'Usuario eliminado correctamente']);
    }




}