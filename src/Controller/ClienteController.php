<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Repository\ClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/cliente')]
final class ClienteController extends AbstractController
{

    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/all', name: 'app_cliente', methods: ['GET'])]
    public function getCliente(ClienteRepository $clienteRepository, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $clientes = $clienteRepository->findAll();

        if (empty($clientes)) {
            return $this->json(['error' => 'No se encontraron clientes'], 404);
        }

        $json = $serializer->serialize($clientes, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
        ]);

        return new JsonResponse($json, 200, [], true);
    }





    #[Route('/{id}', name: 'by_id', methods: ['GET'])]
    public function getById(Cliente $cliente, EntityManagerInterface $entityManager): JsonResponse
    {
        $usuario = $cliente->getUsuario();

        // Si el usuario está relacionado con el cliente, lo inicializamos
        if ($usuario) {
            $entityManager->initializeObject($usuario);  // Cargar la entidad completamente
        }

        if ($usuario) {
            $rol = $usuario->getRoles(); // Obtener el rol del Usuario
        } else {
            // Manejo si no hay un usuario asociado
            return $this->json(['error' => 'El cliente no tiene usuario asociado'], 404);
        }

        // Serializa la información del cliente
        $json = $this->serializer->serialize($cliente, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, 200, [], true);
    }





    #[Route('/guardar', name: 'guardar', methods: ['POST'])]
    public function crearCliente(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $json_cliente = json_decode($request->getContent(), true);

        if (!isset($json_cliente['nombre'], $json_cliente['apellidos'], $json_cliente['DNI'], $json_cliente['foto'], $json_cliente['direccion'], $json_cliente['telefono'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }


        $cliente = new Cliente();
        $cliente->setNombre($json_cliente['nombre']);
        $cliente->setApellidos($json_cliente['apellidos']);
        $cliente->setDNI($json_cliente['DNI']);
        $cliente->setFoto($json_cliente['foto']);
        $cliente->setDireccion($json_cliente['direccion']);
        $cliente->setTelefono($json_cliente['telefono']);


        $entityManager->persist($cliente);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos guardados correctamente'], 201);
    }




    #[Route('/editar/{id}', name: 'editar', methods: ['PUT'])]
    public function editar(int $id, Request $request, EntityManagerInterface $entityManager, ClienteRepository $clienteRepository): JsonResponse
    {
        // Obtén los datos del autor desde el cuerpo de la solicitud
        $json_cliente = json_decode($request->getContent(), true);

        // Busca el autor en la base de datos
        $cliente = $clienteRepository->find($id);

        if (!$cliente) {
            return $this->json(['error' => 'Clientes no encontrado'], 404);
        }

        // Verifica que todos los datos requeridos estén presentes
        if (!isset($json_cliente['nombre'], $json_cliente['apellidos'], $json_cliente['DNI'], $json_cliente['foto'], $json_cliente['direccion'], $json_cliente['telefono'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }


        // Actualiza los datos del autor
        $cliente->setNombre($json_cliente['nombre']);
        $cliente->setApellidos($json_cliente['apellidos']);
        $cliente->setDNI($json_cliente['DNI']);
        $cliente->setFoto($json_cliente['foto']);
        $cliente->setDireccion($json_cliente['direccion']);
        $cliente->setTelefono($json_cliente['telefono']);

        // Guarda los cambios en la base de datos
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos actualizados correctamente']);
    }

    #[Route('/buscar/{nombre}', name: 'buscar_por_nombre', methods: ['GET'])]
    public function buscarPorNombre(string $nombre, ClienteRepository $clienteRepository): JsonResponse
    {
        $clientes = $clienteRepository->buscarPorNombreParcial($nombre);

        if (!$clientes) {
            return $this->json(['error' => 'No se encontraron clientes con ese nombre'], 404);
        }

        $json = $this->serializer->serialize($clientes, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, 200, [], true);
    }





    #[Route('/{id}', name: 'cliente_delete_by_id', methods: ['DELETE'])]
    public function deleteById(int $id, ClienteRepository $clienteRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $cliente = $clienteRepository->find($id);

        if (!$cliente) {
            return $this->json(['error' => 'Autor no encontrado'], 404);
        }

        $entityManager->remove($cliente);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos eliminados correctamente']);
    }

    #[Route('/auth/user', name: 'auth_user', methods: ['GET'])]
    public function getAuthenticatedUser(Security $security): JsonResponse
    {
        $usuario = $security->getUser();

        if (!$usuario) {
            return $this->json(['error' => 'Usuario no autenticado'], 401);
        }

        // Check if Usuario has a Cliente associated
        $cliente = $usuario->getCliente();
        if (!$cliente) {
            return $this->json(['error' => 'No se encontró un cliente asociado al usuario'], 404);
        }

        return $this->json([
            'id' => $usuario->getId(),
            'nick' => $usuario->getNick(),
            'email' => $usuario->getEmail(),
            'rol' => $usuario->getRol(),
            'cliente' => [
                'nombre' => $cliente->getNombre(),
                'apellidos' => $cliente->getApellidos(),
                'dni' => $cliente->getDNI(),
                'telefono' => $cliente->getTelefono(),
                'direccion' => $cliente->getDireccion(),
                'foto' => $cliente->getFoto(),
            ]
        ]);

    }




}
