<?php

namespace App\Controller;

use App\Entity\Resena;
use App\Repository\LibroRepository;
use App\Repository\LineaPedidoRepository;
use App\Repository\PedidoRepository;
use App\Repository\ResenaRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

    private function verificarCompra(int $usuarioId, int $libroId): bool
    {
        $pedidos = $this->pedidoRepository->findByUsuario($usuarioId);
        foreach ($pedidos as $pedido) {
            foreach ($this->lineaPedidoRepository->findByPedido($pedido->getId()) as $linea) {
                if ($linea->getLibro()->getId() === $libroId) {
                    return true;
                }
            }
        }
        return false;
    }

    #[Route("/nueva", name: "nueva_resena", methods: ["POST"])]
    public function nueva(Request $request): JsonResponse
    {
        $dataResena = json_decode($request->getContent(), true);

        if (!isset($dataResena['usuario'], $dataResena['libro'], $dataResena['calificacion'], $dataResena['comentario'])) {
            return new JsonResponse(['mensaje' => 'Datos incompletos.'], Response::HTTP_BAD_REQUEST);
        }

        $usuario = $this->usuarioRepository->find($dataResena['usuario']);
        $libro = $this->libroRepository->find($dataResena['libro']);

        if (!$usuario || !$libro) {
            return new JsonResponse(['mensaje' => 'Usuario o libro no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->verificarCompra($usuario->getId(), $libro->getId())) {
            return new JsonResponse(['mensaje' => 'Debes comprar el libro para reseñarlo.'], Response::HTTP_FORBIDDEN);
        }

        if ($this->resenaRepository->usuarioYaResenoLibro($usuario->getId(), $libro->getId())) {
            return new JsonResponse(['mensaje' => 'Solo puedes hacer una reseña por libro.'], Response::HTTP_CONFLICT);
        }

        if (!is_numeric($dataResena['calificacion']) || $dataResena['calificacion'] < 1 || $dataResena['calificacion'] > 5) {
            return new JsonResponse(['mensaje' => 'La calificación debe ser un número entre 1 y 5.'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($dataResena['comentario']) || strlen($dataResena['comentario']) > 200) {
            return new JsonResponse(['mensaje' => 'Comentario inválido.'], Response::HTTP_BAD_REQUEST);
        }

        $nuevaResena = new Resena();
        $nuevaResena->setUsuario($usuario)
            ->setLibro($libro)
            ->setComentario($dataResena['comentario'])
            ->setFecha(new \DateTime('now'))
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

    #[Route("/mostrar/{id}", name: "mostrar_resena", methods: ["GET"])]
    public function mostrar(int $id): JsonResponse
    {
        $resena = $this->resenaRepository->find($id);
        return $resena ? new JsonResponse([
            'id' => $resena->getId(),
            'usuario' => $resena->getUsuario()->getId(),
            'libro' => $resena->getLibro()->getId(),
            'calificacion' => $resena->getCalificacion(),
            'comentario' => $resena->getComentario(),
            'fecha' => $resena->getFechaFormatted()
        ], Response::HTTP_OK) : new JsonResponse(['mensaje' => 'Reseña no encontrada.'], Response::HTTP_NOT_FOUND);
    }

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
            return new JsonResponse(0, Response::HTTP_OK);
        }

        return new JsonResponse($media, Response::HTTP_OK);
    }



   #[Route("/top-libros", name: "top_libros", methods: ["GET"])]
   public function topLibros(Request $request): JsonResponse
   {
       $limit = $request->query->getInt('limit', 3);
       $topLibros = $this->resenaRepository->findTopRatedBooks($limit);

       return new JsonResponse($topLibros, Response::HTTP_OK);
   }


}
