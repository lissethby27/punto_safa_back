<?php

namespace App\Controller;

use App\Entity\Categoria;
use App\Repository\CategoriaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/categoria')]
final class CategoriaController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }


    /**
     * Obtiene todas las categorías.
     *
     * @param CategoriaRepository $categoriaRepository
     * @return JsonResponse
     */
    #[Route('/all', name: 'app_categoria', methods: ['GET'])]
    public function getCategoria(CategoriaRepository $categoriaRepository): JsonResponse
    {
        // Obtener todas las categorías
        $categoria = $categoriaRepository->findAll();

        // Serializar las categorías a JSON
        $json = $this->serializer->serialize($categoria, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
        ]);


        return new JsonResponse($json, 200, [], true);
    }


    /**
     *
     * Obterner una categoría por su ID.
     *
     * @param int $id
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'categoria_by_id', methods: ['GET'])]
    public function getById(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        // Buscar la categoría en la base de datos
        $categoria = $entityManager->getRepository(Categoria::class)->find($id);

        // Si la categoría no existe, devolver un error 404
        if (!$categoria) {
            return new JsonResponse(['error' => 'Categoría no encontrada'], 404);
        }

        // Serializar la categoría a JSON
        $json = $serializer->serialize($categoria, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        // Devolver la categoría en formato JSON
        return new JsonResponse($json, 200, [], true);
    }


    /**
     *
     * Guardar una nueva categoría.
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/guardar', name: 'guardar_categoria', methods: ['POST'])]
    public function crearCategoria(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Obtener los datos del JSON enviado
        $json_categoria = json_decode($request->getContent(), true);

        // Verificar que los datos requeridos están presentes
        if (!isset($json_categoria['nombre'], $json_categoria['descripcion'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        // Crear una nueva categoría
        $categoria = new Categoria();
        $categoria->setNombre($json_categoria['nombre']);
        $categoria->setDescripcion($json_categoria['descripcion']);

        // Guardar la categoría en la base de datos
        $entityManager->persist($categoria);
        $entityManager->flush();

        // Devolver un mensaje de éxito
        return $this->json(['mensaje' => 'Categoría guardada correctamente'], 201);
    }

    /**
     * Editar una categoría.
     *
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param CategoriaRepository $categoriaRepository
     * @return JsonResponse
     */
    #[Route('/editar/{id}', name: 'editar_categoria', methods: ['PUT'])]
    public function editarCategoria(int $id, Request $request, EntityManagerInterface $entityManager, CategoriaRepository $categoriaRepository): JsonResponse
    {
        // Obtener los datos del JSON enviado
        $json_categoria = json_decode($request->getContent(), true);

        // Buscar la categoría en la base de datos
        $categoria = $categoriaRepository->find($id);

        // Si la categoría no existe, devolver un error 404
        if (!$categoria) {
            return $this->json(['error' => 'Categoría no encontrada'], 404);
        }

        // Verificar que los datos requeridos están presentes
        if (!isset($json_categoria['nombre'], $json_categoria['descripcion'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        // Actualizar los datos de la categoría
        $categoria->setNombre($json_categoria['nombre']);
        $categoria->setDescripcion($json_categoria['descripcion']);

        // Guardar los cambios en la base de datos
        $entityManager->flush();

        // Devolver un mensaje de éxito
        return $this->json(['mensaje' => 'Categoría actualizada correctamente'], 200);
    }


    /**
     *
     * Eliminar una categoría por su ID.
     *
     * @param int $id
     * @param CategoriaRepository $categoriaRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */

    #[Route('/{id}', name: 'categoria_delete_by_id', methods: ['DELETE'])]
    public function deleteCategoria(int $id, CategoriaRepository $categoriaRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // Buscar la categoría en la base de datos
        $categoria = $categoriaRepository->find($id);

        // Si la categoría no existe, devolver un error 404
        if (!$categoria) {
            return $this->json(['error' => 'Categoría no encontrada'], 404);
        }

        // Eliminar la categoría de la base de datos
        $entityManager->remove($categoria);
        $entityManager->flush();

        // Devolver un mensaje de éxito
        return $this->json(['mensaje' => 'Categoría eliminada correctamente'], 200);
    }














}