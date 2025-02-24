<?php

namespace App\Controller;

use App\Entity\Resena;
use App\Repository\LibroRepository;
use App\Repository\ClienteRepository;
use App\Repository\LineaPedidoRepository;
use App\Repository\PedidoRepository;
use App\Repository\ResenaRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;


#[Route("/resena")]
class ResenaController extends AbstractController
{
    private ResenaRepository $resenaRepository;
    private UsuarioRepository $usuarioRepository;
    private LibroRepository $libroRepository;
    private EntityManagerInterface $entityManager;
    private LineaPedidoRepository $lineaPedidoRepository;
    private PedidoRepository $pedidoRepository;

    public function __construct(
        ResenaRepository $resenaRepository,
        UsuarioRepository $usuarioRepository,
        LibroRepository $libroRepository,
        EntityManagerInterface $entityManager,
        LineaPedidoRepository $lineaPedidoRepository,
        PedidoRepository $pedidoRepository
    ) {
        $this->resenaRepository = $resenaRepository;
        $this->usuarioRepository = $usuarioRepository;
        $this->libroRepository = $libroRepository;
        $this->entityManager = $entityManager;
        $this->lineaPedidoRepository = $lineaPedidoRepository;
        $this->pedidoRepository = $pedidoRepository;
    }


    #[Route('/nueva', methods: ['POST'])]
    public function nuevaResena(
        Request $request,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        // Obtener y validar el token JWT
        $token = $request->headers->get('authorization');
        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return new JsonResponse(['mensaje' => 'Token no proporcionado.'], Response::HTTP_UNAUTHORIZED);
        }

        $formatToken = str_replace('Bearer ', '', $token);
        $decodedToken = $jwtManager->decode($formatToken);

        if (!$decodedToken || !isset($decodedToken['id'])) {
            return new JsonResponse(['mensaje' => 'Token inválido.'], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener usuario autenticado
        $usuario = $this->usuarioRepository->find($decodedToken['id']);
        if (!$usuario) {
            return new JsonResponse(['mensaje' => 'Usuario no encontrado.'], Response::HTTP_UNAUTHORIZED);
        }

        // Validar que sea un cliente
        if (!in_array('ROLE_CLIENTE', $usuario->getRoles())) {
            return new JsonResponse(['mensaje' => 'No tienes permisos para hacer una reseña.'], Response::HTTP_FORBIDDEN);
        }

        // Obtener datos de la reseña
        $dataResena = json_decode($request->getContent(), true);

        if (!isset($dataResena['libro'], $dataResena['calificacion'], $dataResena['comentario'])) {
            return new JsonResponse(['mensaje' => 'Datos incompletos.'], Response::HTTP_BAD_REQUEST);
        }

        // Validar el libro
        $libro = $this->libroRepository->find($dataResena['libro']);
        if (!$libro) {
            return new JsonResponse(['mensaje' => 'Libro no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        // Verificar si el usuario ya hizo una reseña para este libro
        if ($this->resenaRepository->findOneBy(['usuario' => $usuario, 'libro' => $libro])) {
            return new JsonResponse(['mensaje' => 'Solo puedes hacer una reseña por libro.'], Response::HTTP_CONFLICT);
        }

        // Verificar si el usuario compró el libro
        if (!$this->verificarCompra($usuario->getId(), $libro->getId())) {
            return new JsonResponse(['mensaje' => 'No has comprado este libro.'], Response::HTTP_FORBIDDEN);
        }

        // Validar calificación
        if (!is_numeric($dataResena['calificacion']) || $dataResena['calificacion'] < 1 || $dataResena['calificacion'] > 5) {
            return new JsonResponse(['mensaje' => 'La calificación debe ser un número entre 1 y 5.'], Response::HTTP_BAD_REQUEST);
        }

        // Validar comentario
        if (empty($dataResena['comentario']) || strlen($dataResena['comentario']) > 200) {
            return new JsonResponse(['mensaje' => 'El comentario no puede estar vacío y debe tener un máximo de 200 caracteres.'], Response::HTTP_BAD_REQUEST);
        }

        // Crear nueva reseña
        $nuevaResena = new Resena();
        $nuevaResena->setUsuario($usuario)
            ->setLibro($libro)
            ->setComentario($dataResena['comentario'])
            ->setFecha(new \DateTime())
            ->setCalificacion($dataResena['calificacion']);

        $this->entityManager->persist($nuevaResena);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $nuevaResena->getId(),
            'usuario' => $usuario->getId(),
            'libro' => $libro->getId(),
            'calificacion' => $nuevaResena->getCalificacion(),
            'comentario' => $nuevaResena->getComentario(),
            'fecha' => $nuevaResena->getFechaFormatted()
        ], Response::HTTP_CREATED);
    }

    /**
     * Verifica si un usuario ha comprado un libro.
     */
    private function verificarCompra(int $usuarioId, int $libroId): bool
    {
        // Lógica para verificar si el usuario ha comprado el libro
        // (Consulta en la base de datos si existe un pedido con el libro y el usuario)
        $pedidos = $this->pedidoRepository->findBy(['usuario' => $usuarioId]);

        foreach ($pedidos as $pedido) {
            $lineasPedido = $this->lineaPedidoRepository->findBy(['pedido' => $pedido->getId(), 'libro' => $libroId]);
            if (!empty($lineasPedido)) {
                return true;
            }
        }

        return false;
    }







// Método para listar todas las reseñas

    #[Route("/listar", name: "listar_resenas", methods: ["GET"])]
    public function listar(): JsonResponse
    {
        return new JsonResponse(array_map(fn($resena) => [
            'id' => $resena->getId(),
            'usuario' => $resena->getUsuario()->getId(),
            'libro' => $resena->getLibro()->getId(),
            'calificacion' => $resena->getCalificacion(),
            'comentario' => $resena->getComentario(),
            'fecha' => $resena->getFechaFormatted()
        ], $this->resenaRepository->findAll()), Response::HTTP_OK);
    }

    //Método para mostrar una reseña en específico





    // Método para actualizar una reseña o editarla

    #[Route("/actualizar/{id}", name: "actualizar_resena", methods: ["PUT"])]
    public function editar(int $id, Request $request): JsonResponse
    {
        $resena = $this->resenaRepository->find($id);
        if (!$resena) {
            return new JsonResponse(['mensaje' => 'Reseña no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $dataResena = json_decode($request->getContent(), true);
        if (isset($dataResena['calificacion']) && (!is_numeric($dataResena['calificacion']) || $dataResena['calificacion'] < 1 || $dataResena['calificacion'] > 5)) {
            return new JsonResponse(['mensaje' => 'Calificación inválida.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($dataResena['comentario']) && (empty($dataResena['comentario']) || strlen($dataResena['comentario']) > 200)) {
            return new JsonResponse(['mensaje' => 'Comentario inválido.'], Response::HTTP_BAD_REQUEST);
        }

        $resena->setCalificacion($dataResena['calificacion'] ?? $resena->getCalificacion())
            ->setComentario($dataResena['comentario'] ?? $resena->getComentario())
            ->setFecha(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse(['mensaje' => 'Reseña actualizada.'], Response::HTTP_OK);
    }

    // Método para ver las reseñas de un libro
    #[Route("/resenas/{id_libro}", name: "mostrar_resenas_libro", methods: ["GET"])]
    public function mostrarResenasPorLibro(int $id_libro): JsonResponse
    {
        $resenas = $this->resenaRepository->findBy(['libro' => $id_libro]);

        if (!$resenas) {
            return new JsonResponse(['mensaje' => 'No hay reseñas para este libro.'], Response::HTTP_OK);
        }

        // Formateamos las reseñas para enviarlas en JSON
        $resenasArray = array_map(function ($resena) {
            return [
                'id'           => $resena->getId(),
                'usuario'      => $resena->getUsuario()->getNick(),
                'libro'        => $resena->getLibro()->getId(),
                'calificacion' => $resena->getCalificacion(),
                'comentario'   => $resena->getComentario(),
                'fecha'        => $resena->getFechaFormatted()
            ];
        }, $resenas);

        return new JsonResponse($resenasArray, Response::HTTP_OK);
    }

    #[Route("/media-calificacion/{id_libro}", name: "media_calificacion_libro", methods: ["GET"])]
    public function mediaCalificacionPorLibro(int $id_libro): JsonResponse
    {
        $media = $this->resenaRepository->calcularMediaCalificacionPorLibro($id_libro);

        if ($media === null) {
            return new JsonResponse(['mensaje' => 'No hay reseñas para este libro.'], Response::HTTP_OK);
        }

        return new JsonResponse(['mediaCalificacion' => $media], Response::HTTP_OK);
    }


    #[Route("/top-libros", name: "top_libros", methods: ["GET"])]
    public function topLibros(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 3);

        if ($limit <= 0) {
            return new JsonResponse(['mensaje' => 'El límite debe ser un número positivo.'], Response::HTTP_BAD_REQUEST);
        }

        $topLibros = array_map(function ($libro) {
            $libro['mediaCalificacion'] = number_format((float)$libro['mediaCalificacion'], 1, '.', '');
            return $libro;
        }, $this->resenaRepository->findTopRatedBooks($limit));

        return new JsonResponse($topLibros, Response::HTTP_OK);
    }


}
