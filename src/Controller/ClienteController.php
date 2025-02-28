<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Usuario;
use App\Repository\ClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;


#[Route('/api/cliente')]
final class ClienteController extends AbstractController
{
    private SerializerInterface $serializer;
    private ClienteRepository $clienteRepository;

    public function __construct(ClienteRepository $clienteRepository, SerializerInterface $serializer)
    {
        $this->clienteRepository = $clienteRepository;
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

    #[Route('/guardar', name: 'guardar', methods: ['POST'])]
    public function crearCliente(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $json_cliente = json_decode($request->getContent(), true);

        // Validar que los campos obligatorios estén presentes
        if (!isset($json_cliente['nombre'], $json_cliente['apellidos'], $json_cliente['DNI'], $json_cliente['foto'], $json_cliente['direccion'], $json_cliente['telefono'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        // Validar que los campos no estén vacíos
        foreach ($json_cliente as $key => $value) {
            if (empty($value)) {
                return $this->json(['error' => "El campo '$key' no puede estar vacío"], 400);
            }
        }

        // Validar el formato del DNI
        if (!preg_match('/^[0-9]{8}[A-Za-z]$/', $json_cliente['DNI'])) {
            return $this->json(['error' => 'El formato del DNI no es válido'], 400);
        }

        // Validar el formato del teléfono (solo 9 dígitos)
        if (!preg_match('/^\d{9}$/', $json_cliente['telefono'])) {
            return $this->json(['error' => 'El teléfono debe contener exactamente 9 dígitos'], 400);
        }


        // Validar que la foto sea una URL válida
        if (!filter_var($json_cliente['foto'], FILTER_VALIDATE_URL)) {
            return $this->json(['error' => 'La URL de la foto no es válida'], 400);
        }

        // Buscar el usuario correspondiente al cliente (si ya se creó previamente)
        $usuario = $entityManager->getRepository(Usuario::class)->findOneBy(['email' => $json_cliente['email']]);
        if (!$usuario) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Crear el cliente
        $cliente = new Cliente();
        $cliente->setNombre($json_cliente['nombre']);
        $cliente->setApellidos($json_cliente['apellidos']);
        $cliente->setDNI($json_cliente['DNI']);
        $cliente->setFoto($json_cliente['foto']);
        $cliente->setDireccion($json_cliente['direccion']);
        $cliente->setTelefono($json_cliente['telefono']);
        $cliente->setUsuario($usuario); // Asignar el usuario existente

        // Persistir y guardar el cliente
        $entityManager->persist($cliente);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos del cliente guardados correctamente'], 201);
    }



    #[Route('/editar/{id}', name: 'editar', methods: ['PUT'])]
    public function editar(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ClienteRepository $clienteRepository
    ): JsonResponse {
        try {
            // Obtener datos del request
            $json_cliente = json_decode($request->getContent(), true);

            if (!$json_cliente) {
                return $this->json(['error' => 'El cuerpo de la solicitud no es un JSON válido'], 400);
            }

            // Buscar el cliente por ID
            $cliente = $clienteRepository->find($id);

            if (!$cliente) {
                return $this->json(['error' => 'Cliente no encontrado'], 404);
            }

            // Verificar que los datos obligatorios existen
            if (!isset($json_cliente['foto'], $json_cliente['direccion'], $json_cliente['telefono'])) {
                return $this->json(['error' => 'Faltan datos obligatorios'], 400);
            }

            // Actualizar datos del cliente
            $cliente->setFoto($json_cliente['foto']);
            $cliente->setDireccion($json_cliente['direccion']);
            $cliente->setTelefono($json_cliente['telefono']);

            // Persistir y guardar cambios
            $entityManager->persist($cliente);
            $entityManager->flush();

            return $this->json(['mensaje' => 'Datos actualizados correctamente'], 200);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
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
    public function getAuthenticatedUser(ClienteRepository $clienteRepository, SerializerInterface $serializer): JsonResponse
    {
        // Obtener el usuario autenticado
        $usuario = $this->getUser();

        if (!$usuario instanceof UserInterface) {
            return $this->json(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener el cliente asociado al usuario usando el método personalizado en el repositorio
        $cliente = $clienteRepository->findOneByUsuario($usuario);  // Aquí se usa el método que creaste

        if (!$cliente) {
            return $this->json(['error' => 'No se encontró cliente asociado a este usuario'], Response::HTTP_NOT_FOUND);
        }

        // Serializar la respuesta
        $json = $serializer->serialize($cliente, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @throws JWTDecodeFailureException
     */
    #[Route('/api/cliente/token-decode', name: 'decode_cliente_token', methods: ['POST'])]
    public function decodeToken(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $token = $request->request->get('token'); // Token enviado en la petición

        if (!$token) {
            return $this->json(['error' => 'Token no proporcionado'], 400);
        }

        $decoded = $jwtManager->decode($token);

        if (!$decoded) {
            return $this->json(['error' => 'Token inválido'], 400);
        }

        return $this->json($decoded);
    }



}


