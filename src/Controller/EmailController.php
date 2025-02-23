<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
}