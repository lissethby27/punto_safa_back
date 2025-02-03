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

    /**
     * @param ResenaRepository $resenaRepository
     * @param UsuarioRepository $usuarioRepository
     * @param LibroRepository $libroRepository
     * @param EntityManagerInterface $entityManager
     * @param LineaPedidoRepository $lineaPedidoRepository
     * @param PedidoRepository $pedidoRepository
     */
    public function __construct(ResenaRepository $resenaRepository, UsuarioRepository $usuarioRepository, LibroRepository $libroRepository, EntityManagerInterface $entityManager, LineaPedidoRepository $lineaPedidoRepository, PedidoRepository $pedidoRepository)
    {
        $this->resenaRepository = $resenaRepository;
        $this->usuarioRepository = $usuarioRepository;
        $this->libroRepository = $libroRepository;
        $this->entityManager = $entityManager;
        $this->lineaPedidoRepository = $lineaPedidoRepository;
        $this->pedidoRepository = $pedidoRepository;
    }

    private function verificarCompra(int $usuarioId, int $libroId): bool
    {
        // Verificar si el usuario ha comprado el libro
        $pedidos = $this->pedidoRepository->findByUsuario($usuarioId);
        foreach ($pedidos as $pedido) {
            $lineas = $this->lineaPedidoRepository->findByPedido($pedido->getId());
            foreach ($lineas as $linea) {
                if ($linea->getLibro()->getId() === $libroId) {
                    return true; // El usuario compró el libro
                }
            }
        }
        return false; // El usuario no ha comprado el libro
    }




    #[Route("/nueva", name: "nueva_resena", methods: ["POST"])]
    public function nueva(Request $request): JsonResponse
    {
        // Decodificar los datos de la petición y validar que existen porque son requeridos para crear una reseña
        $dataResena = json_decode($request->getContent(), true);

        if (!isset($dataResena['usuario'], $dataResena['libro'], $dataResena['calificacion'], $dataResena['comentario'])) {
            return new JsonResponse(['mensaje' => 'Datos incompletos. Se requieren usuario, libro, calificación y comentario.'], Response::HTTP_BAD_REQUEST);
        }

        $usuario = $this->usuarioRepository->find($dataResena['usuario']);
        $libro = $this->libroRepository->find($dataResena['libro']);

        if (!$usuario) {
            return new JsonResponse(['mensaje' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
        }
        if (!$libro) {
            return new JsonResponse(['mensaje' => 'Libro no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        // Verificar si el usuario ha comprado el libro
        if (!$this->verificarCompra($usuario->getId(), $libro->getId())) {
            return new JsonResponse(['mensaje' => 'El usuario debe haber comprado el libro para poder dejar una reseña.'], Response::HTTP_FORBIDDEN);
        }

        // Verificar si el usuario ya ha reseñado el libro
        $yaReseno = $this->resenaRepository->usuarioYaResenoLibro($usuario->getId(), $libro->getId());
        if ($yaReseno) {
            return new JsonResponse(['mensaje' => 'Solo puedes hacer una reseña por libro.'], Response::HTTP_CONFLICT);
        }

        // Verificar si el usuario ya ha hecho una reseña para este libro (Error 409 Conflict)
        $yaReseno = $this->resenaRepository->usuarioYaResenoLibro($usuario->getId(), $libro->getId());
        if ($yaReseno) {
            return new JsonResponse(['mensaje' => 'Solo puedes hacer una reseña por libro.'], Response::HTTP_CONFLICT);
        }

        // Validar que la calificación es un número entre 1 y 5
        if (!is_numeric($dataResena['calificacion']) || $dataResena['calificacion'] < 1 || $dataResena['calificacion'] > 5) {
            return new JsonResponse(['mensaje' => 'La calificación debe ser un número entre 1 y 5.'], Response::HTTP_BAD_REQUEST);
        }

        //Validar que el comentario no esté vacío ni sea nulo ni tenga más de 200 caracteres
        if (empty($dataResena['comentario']) || strlen($dataResena['comentario']) > 200) {
            return new JsonResponse(['mensaje' => 'El comentario no puede estar vacío ni tener más de 200 caracteres.'], Response::HTTP_BAD_REQUEST);
        }


        $nuevaResena = new Resena();
        $nuevaResena->setUsuario($usuario);
        $nuevaResena->setLibro($libro);
        $nuevaResena->setComentario($dataResena['comentario']);
        $nuevaResena->setFecha(new \DateTime('now'));
        $nuevaResena->setCalificacion($dataResena['calificacion']);

        $this->entityManager->persist($nuevaResena);
        $this->entityManager->flush();

        // Devolver la nueva reseña creada con el código 201 Created
        return new JsonResponse([
            'id'           => $nuevaResena->getId(),
            'usuario'      => $nuevaResena->getUsuario()->getId(),
            'libro'        => $nuevaResena->getLibro()->getId(),
            'calificacion' => $nuevaResena->getCalificacion(),
            'comentario'   => $nuevaResena->getComentario(),
            'fecha'        => $nuevaResena->getFechaFormatted()
        ], Response::HTTP_CREATED);
    }

    #[Route("/listar", name: "listar_resenas", methods: ["GET"])]
    public function listar(): JsonResponse
    {
        $resenas = $this->resenaRepository->findAll();
        $data = [];

        foreach ($resenas as $resena) {
            $data[] = [
                'id'           => $resena->getId(),
                'usuario'      => $resena->getUsuario()->getId(),
                'libro'        => $resena->getLibro()->getId(),
                'calificacion' => $resena->getCalificacion(),
                'comentario'   => $resena->getComentario(),
                'fecha'        => $resena->getFechaFormatted()
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route("/mostrar/{id}", name: "mostrar_resena", methods: ["GET"])]
    public function mostrar(int $id): JsonResponse
    {
        $resena = $this->resenaRepository->find($id);

        if (!$resena) {
            return new JsonResponse(['mensaje' => 'Reseña no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        // Devolver la reseña con el código 200 OK
        return new JsonResponse([
            'id'           => $resena->getId(),
            'usuario'      => $resena->getUsuario()->getId(),
            'libro'        => $resena->getLibro()->getId(),
            'calificacion' => $resena->getCalificacion(),
            'comentario'   => $resena->getComentario(),
            'fecha'        => $resena->getFechaFormatted()
        ], Response::HTTP_OK);
    }


    #[Route("/actualizar/{id}", name: "actualizar_resena", methods: ["PUT"])]
    public function editar(int $id, Request $request): JsonResponse

    {
        $resena = $this->resenaRepository->find($id);
        $resena->setFecha(new \DateTime('now'));

        //Si la reseña no existe, devolver un error 404 Not Found
        if (!$resena) {
            return new JsonResponse(['mensaje' => 'Reseña no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        // Decodificar los datos de la petición y validar que existen porque son requeridos para actualizar
        $dataResena = json_decode($request->getContent(), true);

        // Validar que los datos requeridos existen
        if (isset($dataResena['usuario'])) {
            $usuario = $this->usuarioRepository->find($dataResena['usuario']);
            if (!$usuario) {
                return new JsonResponse(['mensaje' => 'Usuario no encontrado.'], Response::HTTP_NOT_FOUND);
            }
            $resena->setUsuario($usuario);
        }

        if (isset($dataResena['libro'])) {
            $libro = $this->libroRepository->find($dataResena['libro']);
            if (!$libro) {
                return new JsonResponse(['mensaje' => 'Libro no encontrado.'], Response::HTTP_NOT_FOUND);
            }
            $resena->setLibro($libro);
        }

        if (isset($dataResena['calificacion'])) {
            if (!is_numeric($dataResena['calificacion']) || $dataResena['calificacion'] < 1 || $dataResena['calificacion'] > 5) {
                return new JsonResponse(['mensaje' => 'La calificación debe ser un número entre 1 y 5.'], Response::HTTP_BAD_REQUEST);
            }
            $resena->setCalificacion($dataResena['calificacion']);
        }

        if (isset($dataResena['comentario'])) {
            $resena->setComentario($dataResena['comentario']);
        }
        // Validar que la fecha es válida y actualizar si existe en los datos de la petición
        if (isset($dataResena['fecha'])) {
            $resena->setFecha(new \DateTime($dataResena['fecha']));
        }

        $this->entityManager->flush();

        return new JsonResponse(['mensaje' => 'Reseña actualizada correctamente.', 'fecha_edicion' => $resena->getFechaFormatted()], Response::HTTP_OK);

    }

    #[Route("/eliminar/{id}", name: "eliminar_resena", methods: ["DELETE"])]
    public function eliminar(int $id): JsonResponse
    {
        $resena = $this->resenaRepository->find($id);

        if (!$resena) {
            return new JsonResponse(['mensaje' => 'Reseña no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($resena);
        $this->entityManager->flush();

        return new JsonResponse(['mensaje' => 'Reseña eliminada correctamente.'], Response::HTTP_OK);
    }
}
