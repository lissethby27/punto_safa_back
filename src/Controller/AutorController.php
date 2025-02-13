<?php

namespace App\Controller;

use App\Entity\Autor;
use App\Repository\AutorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/autor')]
final class AutorController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    #[Route('/all', name: 'app_autor', methods: ['GET'])]
    public function getAutor(AutorRepository $autorRepository): JsonResponse
    {
        // Obtener todos los autores de la base de datos
        $autores = $autorRepository->findAll();

        // Verificar si no hay autores en la base de datos
        if (empty($autores)) {
            return $this->json(['error' => 'No se encontraron autores'], 404);
        }

        // Serializar los autores a formato JSON
        $json = $this->serializer->serialize($autores, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId(); // Manejo de referencia circular
            },
        ]);

        // Retornar la respuesta en formato JSON
        return new JsonResponse($json, 200, [], true);
    }



    #[Route('/{id}', name: 'by_id', methods: ['GET'])]
    public function getById(int $id, AutorRepository $autorRepository): JsonResponse
    {
        $autor = $autorRepository->find($id);

        if (!$autor) {
            return $this->json(['error' => 'Autor no encontrado'], 404);
        }

        $json = $this->serializer->serialize($autor, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, 200, [], true);
    }





    #[Route('/guardar', name: 'guardar', methods: ['POST'])]
    public function crearAutor(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $json_autor = json_decode($request->getContent(), true);

        if (!isset($json_autor['nombre'], $json_autor['apellidos'], $json_autor['biografia'], $json_autor['nacionalidad'], $json_autor['fecha_nacimiento'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        $fechaNacimiento = \DateTime::createFromFormat('Y-m-d', $json_autor['fecha_nacimiento']);
        if (!$fechaNacimiento) {
            return $this->json(['error' => 'Formato de fecha incorrecto'], 400);
        }

        $autor = new Autor();
        $autor->setNombre($json_autor['nombre']);
        $autor->setApellidos($json_autor['apellidos']);
        $autor->setBiografia($json_autor['biografia']);
        $autor->setNacionalidad($json_autor['nacionalidad']);
        $autor->setFechaNacimiento($fechaNacimiento);

        $entityManager->persist($autor);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos guardados correctamente'], 201);
    }


    #[Route('/editar/{id}', name: 'editar', methods: ['PUT'])]
    public function editar(int $id, Request $request, EntityManagerInterface $entityManager, AutorRepository $autorRepository): JsonResponse
    {
        // Obtén los datos del autor desde el cuerpo de la solicitud
        $json_autor = json_decode($request->getContent(), true);

        // Busca el autor en la base de datos
        $autor = $autorRepository->find($id);

        if (!$autor) {
            return $this->json(['error' => 'Autor no encontrado'], 404);
        }

        // Verifica que todos los datos requeridos estén presentes
        if (!isset($json_autor['nombre'], $json_autor['apellidos'], $json_autor['biografia'], $json_autor['nacionalidad'], $json_autor['fecha_nacimiento'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        // Valida el formato de la fecha
        $fechaNacimiento = \DateTime::createFromFormat('Y-m-d', $json_autor['fecha_nacimiento']);
        if (!$fechaNacimiento) {
            return $this->json(['error' => 'Formato de fecha incorrecto'], 400);
        }

        // Actualiza los datos del autor
        $autor->setNombre($json_autor['nombre']);
        $autor->setApellidos($json_autor['apellidos']);
        $autor->setBiografia($json_autor['biografia']);
        $autor->setNacionalidad($json_autor['nacionalidad']);
        $autor->setFechaNacimiento($fechaNacimiento);

        // Guarda los cambios en la base de datos
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos actualizados correctamente']);
    }

    #[Route('/buscar/{nombre}', name: 'buscar_por_nombre', methods: ['GET'])]
    public function buscarPorNombre(string $nombre, AutorRepository $autorRepository): JsonResponse
    {
        $autores = $autorRepository->buscarPorNombreParcial($nombre);

        if (!$autores) {
            return $this->json(['error' => 'No se encontraron autores con ese nombre'], 404);
        }

        $json = $this->serializer->serialize($autores, 'json', [
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, 200, [], true);
    }





    #[Route('/{id}', name: 'autor_delete_by_id', methods: ['DELETE'])]
    public function deleteById(int $id, AutorRepository $autorRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $autor = $autorRepository->find($id);

        if (!$autor) {
            return $this->json(['error' => 'Autor no encontrado'], 404);
        }

        $entityManager->remove($autor);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos eliminados correctamente']);
    }



}
