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


    /**
     *
     * Obtiene todos los clientes
     *
     * @param ClienteRepository $clienteRepository
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/all', name: 'app_cliente', methods: ['GET'])]
    public function getCliente(ClienteRepository $clienteRepository, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        // Obtener todos los clientes
        $clientes = $clienteRepository->findAll();

        // Si no hay clientes, devolver un error
        if (empty($clientes)) {
            return $this->json(['error' => 'No se encontraron clientes'], 404);
        }

        // Serializar la respuesta
        $json = $serializer->serialize($clientes, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
        ]);

        // Devolver la respuesta
        return new JsonResponse($json, 200, [], true);
    }


    /**
     *
     * Obtiene un cliente por su ID
     *
     * @param Cliente $cliente
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
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

    /**
     *
     * Guardar un nuevo cliente.
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
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




    /**
     *
     * Editar un cliente por su ID
     *
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ClienteRepository $clienteRepository
     * @return JsonResponse
     */
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
                return $this->json(['error' => 'JSON inválido: ' . json_last_error_msg()], 400);
            }

            // Buscar el cliente por ID
            if (!$json_cliente) {
                return $this->json(['error' => 'El cuerpo de la solicitud no es un JSON válido'], 400);
            }

            // Buscar el cliente por ID
            $cliente = $clienteRepository->find($id);

            // Si no se encuentra el cliente, devolver un error
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
            $entityManager->flush();
            error_log("Cliente actualizado con ID: " . $cliente->getId());
            $entityManager->clear();

            // Devolver respuesta
            return $this->json(['mensaje' => 'Datos actualizados correctamente'], 200);

            // Capturar excepciones
        } catch (\Exception $e) {

            // Devolver error
            return $this->json(['error' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }


    /**
     *
     * Obtener clientes por nombre.
     *
     * @param string $nombre
     * @param ClienteRepository $clienteRepository
     * @return JsonResponse
     */
    #[Route('/buscar/{nombre}', name: 'buscar_por_nombre', methods: ['GET'])]
    public function buscarPorNombre(string $nombre, ClienteRepository $clienteRepository): JsonResponse
    {
        // Buscar clientes por nombre
        $clientes = $clienteRepository->buscarPorNombreParcial($nombre);

        // Si no se encuentran clientes, devolver un error
        if (!$clientes) {
            return $this->json(['error' => 'No se encontraron clientes con ese nombre'], 404);
        }


        // Serializar la respuesta
        $json = $this->serializer->serialize($clientes, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        // Devolver la respuesta
        return new JsonResponse($json, 200, [], true);
    }

    /**
     *
     * Eliminar un cliente por su ID
     *
     * @param int $id
     * @param ClienteRepository $clienteRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */

    #[Route('/{id}', name: 'cliente_delete_by_id', methods: ['DELETE'])]
    public function deleteById(int $id, ClienteRepository $clienteRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // Buscar el cliente por ID
        $cliente = $clienteRepository->find($id);

        // Si no se encuentra el cliente, devolver un error
        if (!$cliente) {
            return $this->json(['error' => 'Autor no encontrado'], 404);
        }

        // Eliminar el cliente
        $entityManager->remove($cliente);
        $entityManager->flush();


        // Devolver respuesta
        return $this->json(['mensaje' => 'Datos eliminados correctamente']);
    }

    /**
     *
     * Obtener el usuario autenticado
     *
     * @param ClienteRepository $clienteRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */

    #[Route('/auth/user', name: 'auth_user', methods: ['GET'])]
    public function getAuthenticatedUser(ClienteRepository $clienteRepository, SerializerInterface $serializer): JsonResponse
    {
        // Obtener el usuario autenticado
        $usuario = $this->getUser();

        // Si el usuario no está autenticado, devolver un error
        if (!$usuario instanceof UserInterface) {
            return $this->json(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener el cliente asociado al usuario usando el método personalizado en el repositorio
        $cliente = $clienteRepository->findOneByUsuario($usuario);

        // Si no se encuentra el cliente, devolver un error
        if (!$cliente) {
            return $this->json(['error' => 'No se encontró cliente asociado a este usuario'], Response::HTTP_NOT_FOUND);
        }

        // Serializar la respuesta
        $json = $serializer->serialize($cliente, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        // Devolver la respuesta
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     *
     * Decodificar un token JWT
     *
     * @param Request $request
     * @param JWTTokenManagerInterface $jwtManager
     * @return JsonResponse
     */
    #[Route('/api/cliente/token-decode', name: 'decode_cliente_token', methods: ['POST'])]
    public function decodeToken(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        // Obtener el token de la petición
        $token = $request->request->get('token'); // Token enviado en la petición

        // Si no se proporciona un token, devolver un error
        if (!$token) {
            return $this->json(['error' => 'Token no proporcionado'], 400);
        }

        // Decodificar el token
        $decoded = $jwtManager->decode($token);

        // Si el token no es válido, devolver un error
        if (!$decoded) {
            return $this->json(['error' => 'Token inválido'], 400);
        }


        // Devolver la respuesta
        return $this->json($decoded);
    }



}


