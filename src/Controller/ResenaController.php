<?php

namespace App\Controller;

use App\Entity\LineaPedido;
use App\Entity\Resena;
use App\Repository\LineaPedidoRepository;
use App\Repository\PedidoRepository;
use App\Repository\ResenaRepository;
use App\Repository\UsuarioRepository;
use App\Repository\LibroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

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


    #[Route("/nueva", name: "nueva_resena", methods: ["POST"])]
    public function nueva(Request $request): JsonResponse
    {
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

       //Tengo que verificar SI EL USUARIO HA COMPRADO EL LIBRO PARA PODER HACER LA RESEÑA CORRESPONDIENTE


        // Verificar si el usuario ya ha hecho una reseña para este libro (Error 409 Conflict)
        $yaReseno = $this->resenaRepository->usuarioYaResenoLibro($usuario->getId(), $libro->getId());
        if ($yaReseno) {
            return new JsonResponse(['mensaje' => 'Solo puedes hacer una reseña por libro.'], Response::HTTP_CONFLICT);
        }

        // Validar que la calificación es un número entre 1 y 5
        if (!is_numeric($dataResena['calificacion']) || $dataResena['calificacion'] < 1 || $dataResena['calificacion'] > 5) {
            return new JsonResponse(['mensaje' => 'La calificación debe ser un número entre 1 y 5.'], Response::HTTP_BAD_REQUEST);
        }

        $nuevaResena = new Resena();
        $nuevaResena->setUsuario($usuario);
        $nuevaResena->setLibro($libro);
        $nuevaResena->setComentario($dataResena['comentario']);
        $nuevaResena->setFecha(new \DateTime('now'));
        $nuevaResena->setCalificacion($dataResena['calificacion']);

        $this->entityManager->persist($nuevaResena);
        $this->entityManager->flush();

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

        return new JsonResponse(['mensaje' => 'Reseña actualizada correctamente.'], Response::HTTP_OK);

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
