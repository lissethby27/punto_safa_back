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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;


#[Route('/api')]
final class UsuarioController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;
    private EntityManagerInterface $entityManager;


    public function __construct(UsuarioRepository $usuarioRepository, EntityManagerInterface $entityManager)
    {
        dump("Constructor ejecutado");
        $this->usuarioRepository = $usuarioRepository;
        $this->entityManager = $entityManager;
        die;
    }


    #[Route('/registro', name: 'app_usuario', methods: ['POST'])]
    public function registro(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UrlGeneratorInterface $urlGenerator,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $body = json_decode($request->getContent(), true);

        // Validar datos antes de continuar
        $errores = $this->validarDatos($body);
        if (!empty($errores)) {
            return new JsonResponse(['error' => $errores], 400);
        }

        // Verificar si el email o el nick ya existen
        if ($this->entityManager->getRepository(Usuario::class)->findOneBy(['email' => $body['email']])) {
            return new JsonResponse(['error' => 'El email ya está registrado'], 400);
        }

        // Verificar que dni no esté registrado
        if ($this->entityManager->getRepository(Cliente::class)->findOneBy(['dni' => $body['dni']])) {
            return new JsonResponse(['error' => 'El DNI ya está registrado'], 400);
        }

        //Verificar que el teléfono no esté registrado
        if ($this->entityManager->getRepository(Cliente::class)->findOneBy(['telefono' => $body['telefono']])) {
            return new JsonResponse(['error' => 'El teléfono ya está registrado'], 400);
        }

        if ($this->entityManager->getRepository(Usuario::class)->findOneBy(['nick' => $body['nick']])) {
            return new JsonResponse(['error' => 'El nick ya está registrado'], 400);
        }


        if (!isset($this->entityManager)) {
            return new JsonResponse(['error' => 'EntityManager no está disponible'], 500);
        }


        // Crear usuario
        $nuevo_usuario = new Usuario();
        $nuevo_usuario->setNick($body['nick']);
        $nuevo_usuario->setEmail($body['email']);
        $nuevo_usuario->setContrasena($userPasswordHasher->hashPassword($nuevo_usuario, $body['contrasena']));
        $nuevo_usuario->setRol("cliente");
        $nuevo_usuario->setVerificado(false);

        $this->entityManager->persist($nuevo_usuario);
        $this->entityManager->flush();

        return new JsonResponse(['mensaje' => 'Usuario registrado. Revisa tu email para obtener el código de verificación.'], 201);
    }


    /**
     * Función para validar los datos de entrada.
     */
    private function validarDatos(array $body): array
    {
        $errores = [];

        // Verificar si existen los campos requeridos
        $campos_requeridos = ['nick', 'email', 'contrasena', 'repetircontrasena', 'nombre', 'apellidos', 'dni', 'foto', 'direccion', 'telefono'];
        foreach ($campos_requeridos as $campo) {
            if (!isset($body[$campo])) {
                $errores[] = "Falta el campo obligatorio: $campo.";
            }
        }

        if (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $body['nick'])) {
            $errores[] = 'Nick no válido (mínimo 3 caracteres y solo letras y números).';
        }

        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/\.(com|es)$/', $body['email'])) {
            $errores[] = 'Email no válido. Debe terminar en .com o .es.';
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

        if (!filter_var($body['foto'], FILTER_VALIDATE_URL)) {
            $errores[] = 'URL de foto no válida.';
        }

        if (!preg_match('/^[67][0-9]{8}$/', $body['telefono'])) {
            $errores[] = 'Teléfono no válido (deben ser 9 dígitos numéricos).';
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/', $body['contrasena'])) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres, incluir una mayúscula, una minúscula y un número.';
        }

        if ($body['contrasena'] !== $body['repetircontrasena']) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        $errores = $this->validarDatos($body);
        dump($errores); die;

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