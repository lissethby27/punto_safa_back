<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;


#[Route('/api')]
final class UsuarioController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;
    private EntityManagerInterface $entityManager;


    public function __construct(UsuarioRepository $usuarioRepository, EntityManagerInterface $entityManager)
    {
        $this->usuarioRepository = $usuarioRepository;
        $this->entityManager = $entityManager;

    }


    #[Route('/registro', name: 'app_usuario', methods: ['POST'])]
    public function registro(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        // Validar que los campos obligatorios estén presentes
        if (!isset($body['nick'], $body['email'], $body['contrasena'], $body['nombre'], $body['apellidos'], $body['dni'], $body['foto'], $body['direccion'], $body['telefono'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        // Validar que los campos no estén vacíos
        foreach ($body as $key => $value) {
            if (empty($value)) {
                return $this->json(['error' => "El campo '$key' no puede estar vacío"], 400);
            }
        }

        // Validar la contraseña
        $password = $body['contrasena'];
        if (strlen($password) < 8 || strlen($password) > 32) {
            return $this->json(['error' => 'La contraseña debe tener entre 8 y 32 caracteres'], 400);
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return $this->json(['error' => 'La contraseña debe contener al menos una letra mayúscula'], 400);
        }
        if (!preg_match('/[a-z]/', $password)) {
            return $this->json(['error' => 'La contraseña debe contener al menos una letra minúscula'], 400);
        }
        if (!preg_match('/[0-9]/', $password)) {
            return $this->json(['error' => 'La contraseña debe contener al menos un número'], 400);
        }
        if (!preg_match('/[\W_]/', $password)) { // Caracter especial
            return $this->json(['error' => 'La contraseña debe contener al menos un carácter especial'], 400);
        }

        // Validar si el correo electrónico ya está en uso
        $usuarioExistente = $entityManager->getRepository(Usuario::class)->findOneBy(['email' => $body['email']]);
        if ($usuarioExistente) {
            return $this->json(['error' => 'El correo electrónico ya está registrado'], 400);
        }

        // Validar si el DNI ya está en uso
        $dniExistente = $entityManager->getRepository(Cliente::class)->findOneBy(['DNI' => $body['dni']]);
        if ($dniExistente) {
            return $this->json(['error' => 'El DNI ya está registrado'], 400);
        }

        // Validar si el nick ya está en uso (insensible a mayúsculas/minúsculas)
        $nickExistente = $entityManager->getRepository(Usuario::class)->findOneBy(['nick' => strtolower($body['nick'])]);  // Buscamos el nick sin importar las mayúsculas/minúsculas
        if ($nickExistente) {
            return $this->json(['error' => 'El nick ya está registrado'], 400);
        }

        // Crear el nuevo usuario
        $nuevo_usuario = new Usuario();
        $nuevo_usuario->setNick(strtolower($body['nick']));  // Guardamos el nick en minúsculas
        $nuevo_usuario->setEmail($body['email']);
        $nuevo_usuario->setContrasena($userPasswordHasher->hashPassword($nuevo_usuario, $body['contrasena']));
        $nuevo_usuario->setRol("cliente");

        // Crear un token de activación
        $token = base64_encode($body['email']); // Codificar email
        $token = rtrim(strtr($token, '+/', '-_'), '='); // Hacerlo URL-safe
// Hacerlo seguro para URLs

        // Crear el cliente asociado al usuario
        $nuevo_cliente = new Cliente();
        $nuevo_cliente->setNombre($body['nombre']);
        $nuevo_cliente->setApellidos($body['apellidos']);
        $nuevo_cliente->setDNI($body['dni']);
        $nuevo_cliente->setFoto($body['foto']);
        $nuevo_cliente->setDireccion($body['direccion']);
        $nuevo_cliente->setTelefono($body['telefono']);
        $nuevo_cliente->setUsuario($nuevo_usuario); // Asignar el usuario al cliente

        // Guardar primero el usuario, luego el cliente
        $entityManager->persist($nuevo_usuario); // Guardamos el usuario
        $entityManager->persist($nuevo_cliente); // Luego guardamos el cliente
        $entityManager->flush(); // Guardamos ambos cambios en la base de datos

        // Enviar el correo de activación al usuario
        $email = (new Email())
            ->from('puntosafalibreria@gmail.com')
            ->to($body['email'])
            ->subject('Activa tu cuenta')
            ->html("<p>Haz clic en el enlace para activar tu cuenta:</p>
<p><a href='http://localhost:8000/api/activar/{$token}'>Activar cuenta</a></p>");



        // Enviar el correo
        $mailer->send($email);

        // Responder que el usuario fue creado correctamente
        return new JsonResponse(['mensaje' => 'Usuario y cliente registrados correctamente. Por favor, revisa tu correo para activar tu cuenta.'], 201);
    }



    #[Route('/activar/{token}', name: 'activar_cuenta', methods: ['GET'])]
    public function activarCuenta(string $token): Response
    {
        // Decodificar el email desde el token
        $email = base64_decode(strtr($token, '-_', '+/'), true);

        // Validar que el email es correcto
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response('Token de activación inválido', 400);
        }

        // Buscar el usuario por email
        $usuario = $this->usuarioRepository->findOneBy(['email' => $email]);

        if (!$usuario) {
            return new Response('Usuario no encontrado', 404);
        }

        // Activar la cuenta (cambiando su rol)
        $usuario->setRol('cliente');

        // Guardar cambios
        $this->entityManager->flush();

        // Redirigir al frontend en Angular
        return new RedirectResponse('http://localhost:4200/home');
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
        //$usuario->setNick($json_usuario['nick']);
        $usuario->setContrasena($json_usuario['contrasena']);
        //$usuario->setRol($json_usuario['rol']);
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


    #[Route('/api/admin-data', name: 'admin_data', methods: ['GET'])]
    public function getAdminData(UsuarioRepository $usuarioRepository, SerializerInterface $serializer): JsonResponse
    {
        // Obtener el usuario autenticado
        $usuario = $this->getUser(); // Symfony se encarga de esto cuando el usuario está autenticado

        if (!$usuario || $usuario->getRol() !== 'admin') {
            // Si el usuario no está autenticado o no tiene el rol de 'admin', retorna un error
            return $this->json(['error' => 'Acceso no autorizado. Solo admins pueden ver esta información.'], 403);
        }

        // Obtener los datos del admin (puedes modificar esto para obtener más información si es necesario)
        $json = $serializer->serialize($usuario, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId(); // Evita referencias circulares
            },
        ]);

        // Retornar los datos del admin
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/usuario/{nick}', methods: ['GET'])]
    public function obtenerCorreo(string $nick, UsuarioRepository $usuarioRepository): JsonResponse
    {
        // Buscar el usuario por 'nick'
        $usuario = $usuarioRepository->findOneBy(['nick' => $nick]);

        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }

        return new JsonResponse([
            'nick' => $usuario->getNick(),
            'email' => $usuario->getEmail()
        ]);
    }


    /**
     * @throws JWTDecodeFailureException
     */
    #[Route('/api/usuario/token-decode', name: 'decode_token', methods: ['GET'])]
    public function decodeToken(JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $user = $this->getUser(); // Obtiene el usuario autenticado

        if (!$user) {
            return $this->json(['error' => 'No autenticado'], 401);
        }

        $tokenData = $jwtManager->decode($this->get('security.token_storage')->getToken());

        return $this->json($tokenData);
    }







}