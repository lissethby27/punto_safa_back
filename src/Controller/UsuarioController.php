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
use Symfony\Component\Validator\Validator\ValidatorInterface;



#[Route('/api')]
final class UsuarioController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }


    #[Route('/registro', name: 'app_usuario', methods: ['POST'])]
    public function registro(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $body = json_decode($request->getContent(), true);

        // Verificar si el usuario ya existe
        if ($entityManager->getRepository(Usuario::class)->findOneBy(['email' => $body['email']])) {
            return new JsonResponse(['error' => 'El email ya está registrado'], 400);
        }

        // Validaciones manuales
        $errores = $this->validarDatos($body);
        if (!empty($errores)) {
            return new JsonResponse(['error' => $errores], 400);
        }

        // Crear usuario
        $nuevo_usuario = new Usuario();
        $nuevo_usuario->setNick($body['nick']);
        $nuevo_usuario->setEmail($body['email']);
        $nuevo_usuario->setContrasena($userPasswordHasher->hashPassword($nuevo_usuario, $body['contrasena']));
        $nuevo_usuario->setRol("cliente");
        $nuevo_usuario->setVerificado(false);

        $entityManager->persist($nuevo_usuario);
        $entityManager->flush();

        // Crear cliente asociado
        $nuevo_cliente = new Cliente();
        $nuevo_cliente->setNombre($body['nombre']);
        $nuevo_cliente->setApellidos($body['apellidos']);
        $nuevo_cliente->setDNI($body['dni']);
        $nuevo_cliente->setFoto($body['foto']);
        $nuevo_cliente->setDireccion($body['direccion']);
        $nuevo_cliente->setTelefono($body['telefono']);
        $nuevo_cliente->setUsuario($nuevo_usuario);

        $entityManager->persist($nuevo_cliente);
        $entityManager->flush();

        // Generar token de verificación
        $tokenVerificacion = $jwtManager->createFromPayload($nuevo_usuario, [
            'verificacion' => true,
            'exp' => time() + 3600 // Expira en 1 hora
        ]);

        // Generar enlace de verificación
        $verificacionUrl = $urlGenerator->generate(
            'verificar_email',
            ['token' => $tokenVerificacion],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Enviar email de verificación
        $email = (new Email())
            ->from('noreply@tuapp.com')
            ->to($body['email'])
            ->subject('Confirma tu email')
            ->html("<p>Gracias por registrarte. Verifica tu cuenta aquí:</p>
                <p><a href='$verificacionUrl'>$verificacionUrl</a></p>");

        $mailer->send($email);

        return new JsonResponse(['mensaje' => 'Usuario registrado. Revisa tu email para confirmar la cuenta.'], 201);
    }


    #[Route('/verificar-email/{token}', name: 'verificar_email', methods: ['GET'])]
    public function verificarEmail(
        string $token,
        Request $request,
        UtilidadesToken $utilidadesToken,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        try {
            $decoded = $utilidadesToken->extraerTokenData($request, $jwtManager);

            if (!isset($decoded['email']) || !isset($decoded['verificacion'])) {
                return new JsonResponse(['error' => 'Token inválido'], 400);
            }

            $usuario = $entityManager->getRepository(Usuario::class)->findOneBy(['email' => $decoded['email']]);

            if (!$usuario) {
                return new JsonResponse(['error' => 'Usuario no encontrado'], 400);
            }

            if ($usuario->getVerificado()) {
                return new JsonResponse(['mensaje' => 'El email ya ha sido verificado.'], 200);
            }

            $usuario->setVerificado(true);
            $entityManager->flush();

            return new JsonResponse(['mensaje' => 'Email verificado con éxito. Ahora puedes iniciar sesión.'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido o expirado'], 400);
        }
    }


    /**
     * Función para validar los datos de entrada.
     */
    private function validarDatos(array $body): array
    {
        $errores = [];

        if (!isset($body['nick'], $body['email'], $body['contrasena'], $body['repetircontrasena'],
            $body['nombre'], $body['apellidos'], $body['dni'], $body['foto'], $body['direccion'], $body['telefono'])) {
            $errores[] = 'Faltan datos obligatorios.';
        }

        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Email no válido.';
        }

        if (!preg_match('/^[a-zA-ZÀ-ÿ ]{3,}$/', $body['nombre'])) {
            $errores[] = 'Nombre no válido (mínimo 3 caracteres y solo letras).';
        }

        if (!preg_match('/^[a-zA-ZÀ-ÿ ]{3,}$/', $body['apellidos'])) {
            $errores[] = 'Apellidos no válidos (mínimo 3 caracteres y solo letras).';
        }

        if (!preg_match('/^[0-9]{8}[A-Z]$/', $body['dni'])) {
            $errores[] = 'DNI no válido (debe tener 8 números y una letra mayúscula).';
        }

        if (!preg_match('/^(https?:\/\/)?([\w.-]+)\.([a-z]{2,6}\.?)(\/.*)?$/', $body['foto'])) {
            $errores[] = 'URL de foto no válida.';
        }

        if (!preg_match('^[67][0-9]{8}$', $body['telefono'])) {
            $errores[] = 'Teléfono no válido (deben ser 9 dígitos numéricos).';
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/', $body['contrasena'])) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres, incluir una mayúscula, una minúscula y un número.';
        }

        if ($body['contrasena'] !== $body['repetircontrasena']) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        return $errores;
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




}