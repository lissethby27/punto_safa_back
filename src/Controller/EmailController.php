<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class EmailController extends AbstractController
{
    #[Route('/api/enviar-correo', name: 'enviar_correo', methods: ['POST'])]
    public function enviarCorreo(Request $request, MailerInterface $mailer, LoggerInterface $logger): JsonResponse
    {
        try {
            // Decodificar JSON del request
            $data = json_decode($request->getContent(), true);


            // Validar los campos obligatorios
            if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
                return new JsonResponse(['error' => 'El campo nombre es obligatorio'], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['correo']) || !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(['error' => 'El correo electrónico no es válido'], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['mensaje']) || empty(trim($data['mensaje']))) {
                return new JsonResponse(['error' => 'El campo mensaje es obligatorio'], Response::HTTP_BAD_REQUEST);
            }

            // Sanitizar la entrada del usuario
            $nombre = htmlspecialchars(strip_tags($data['nombre']));
            $correoDestino = htmlspecialchars(strip_tags($data['correo']));
            $mensaje = nl2br(htmlentities(trim($data['mensaje']), ENT_QUOTES, 'UTF-8'));

            // Crear el email
            $email = (new Email())
                ->from('puntosafalibreria@gmail.com') // Debe coincidir con tu MAILER_DSN
                ->to($correoDestino)
                ->subject('Mensaje de tu Librería PuntoSafa')
                ->html("
                    <p><strong>Hola $nombre,</strong></p>
                    <p>Este es un mensaje de tu Librería PuntoSafa:</p>
                    <blockquote>$mensaje</blockquote>
                    <p>Gracias por confiar en <strong>PuntoSafa</strong>.</p>
                ")
                ->attachFromPath($this->getParameter('kernel.project_dir') . '/public/imagen/puntoSafa.png', 'puntoSafa.png', 'image/png') // Adjuntar imagen
                ->embed(fopen($this->getParameter('kernel.project_dir') . '/public/imagen/puntoSafa.png', 'r'), 'logo_cid'); // Embebe la imagen

            // Enviar el email
            $mailer->send($email);

            return new JsonResponse(['message' => 'Correo enviado con éxito'], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'No se pudo enviar el correo', 'detalle' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/recuperar-contrasena', name: 'recuperar_contrasena', methods: ['POST'])]
    public function recuperarContrasena(Request $request, SessionInterface $session, MailerInterface $mailer): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        // Validar que el correo esté presente
        if (!isset($body['email']) || empty($body['email'])) {
            return $this->json(['error' => 'El campo email es obligatorio'], 400);
        }

        $email = $body['email'];

        // Aquí deberías validar que el correo esté registrado, sin necesidad de modificar la base de datos
        // Simulamos que el correo es válido. Si no puedes hacer consultas a la base de datos, simplemente omite esta parte.

        // Crear un token de restablecimiento
        $token = base64_encode(random_bytes(32)); // Genera un token aleatorio

        // Almacenar el token en la sesión para este usuario (temporal)
        $session->set('tokenRestablecimiento_' . $email, $token);

        // Crear un enlace de restablecimiento de contraseña
        $url = 'http://localhost:8000/api/restablecer-contrasena/' . $token;

        // Enviar el correo electrónico con el enlace
        $emailMessage = (new Email())
            ->from('puntosafalibreria@gmail.com')
            ->to($email)
            ->subject('Recuperación de contraseña')
            ->html("<p>Para restablecer tu contraseña, haz clic en el siguiente enlace:</p><p><a href='{$url}'>Restablecer contraseña</a></p>");

        $mailer->send($emailMessage);

        return $this->json(['mensaje' => 'Si el correo existe, se ha enviado un enlace de recuperación.'], 200);
    }

    #[Route('/api/restablecer-contrasena/{token}', name: 'restablecer_contrasena', methods: ['POST'])]
    public function restablecerContrasena(Request $request, $token, UsuarioRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Buscar al usuario con el token
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user) {
            // Si no se encuentra el usuario, devolver un error
            return $this->json(['error' => 'Token inválido'], Response::HTTP_BAD_REQUEST);
        }

        // Obtener la nueva contraseña del cuerpo de la solicitud
        $newPassword = $request->request->get('contraseña');

        if (!$newPassword) {
            // Si no se pasa la nueva contraseña, retornar un error
            return $this->json(['error' => 'El campo contraseña es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        // Hash de la nueva contraseña antes de guardarla
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);

        // Actualizar la contraseña del usuario con la nueva contraseña
        $user->setPassword($hashedPassword);

        // Guardar los cambios en la base de datos
        $entityManager->flush();

        return $this->json(['message' => 'Contraseña restablecida con éxito']);
    }




}