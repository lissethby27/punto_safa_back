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

    #[Route('/all', name: 'app_categoria', methods: ['GET'])]
    public function getCategoria(CategoriaRepository $categoriaRepository): JsonResponse
    {
        $categoria = $categoriaRepository->findAll();

        $json = $this->serializer->serialize($categoria, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
        ]);

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/{id}', name: 'categoria_by_id', methods: ['GET'])]
    public function getById(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $categoria = $entityManager->getRepository(Categoria::class)->find($id);

        if (!$categoria) {
            return new JsonResponse(['error' => 'Categoría no encontrada'], 404);
        }

        $json = $serializer->serialize($categoria, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, 200, [], true);
    }


    #[Route('/guardar', name: 'guardar_categoria', methods: ['POST'])]
    public function crearCategoria(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $json_categoria = json_decode($request->getContent(), true);

        if (!isset($json_categoria['nombre'], $json_categoria['descripcion'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        $categoria = new Categoria();
        $categoria->setNombre($json_categoria['nombre']);
        $categoria->setDescripcion($json_categoria['descripcion']);

        $entityManager->persist($categoria);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Categoría guardada correctamente'], 201);
    }

    #[Route('/editar/{id}', name: 'editar_categoria', methods: ['PUT'])]
    public function editarCategoria(int $id, Request $request, EntityManagerInterface $entityManager, CategoriaRepository $categoriaRepository): JsonResponse
    {
        // Obtener los datos del JSON enviado
        $json_categoria = json_decode($request->getContent(), true);

        // Buscar la categoría en la base de datos
        $categoria = $categoriaRepository->find($id);

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

        return $this->json(['mensaje' => 'Categoría actualizada correctamente'], 200);
    }


    #[Route('/{id}', name: 'categoria_delete_by_id', methods: ['DELETE'])]
    public function deleteCategoria(int $id, CategoriaRepository $categoriaRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $categoria = $categoriaRepository->find($id);

        if (!$categoria) {
            return $this->json(['error' => 'Categoría no encontrada'], 404);
        }

        $entityManager->remove($categoria);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Categoría eliminada correctamente'], 200);
    }












}