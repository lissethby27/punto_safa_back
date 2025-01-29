<?php

namespace App\Controller;

use App\Entity\Autor;
use App\Repository\AutorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/autor')]
final class AutorController extends AbstractController
{
    #[Route('/all', name: 'app_autor', methods: ['GET'])]
    public function getAutor(AutorRepository $autorRepository): JsonResponse
    {
        return $this->json($autorRepository->findAll());
    }

    #[Route('/{id<\d+>}', name: 'autor_by_id', methods: ['GET'])]
    public function getById(Autor $autor): JsonResponse
    {
        return $this->json($autor);
    }

    #[Route('', name: 'save_autor', methods: ['POST'])]
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

        return $this->json(['mensaje' => 'Datos guardados correctamente']);
    }

    #[Route('/{id}', name: 'edit_autor', methods: ['PUT'])]
    public function editar(int $id, Request $request, EntityManagerInterface $entityManager, AutorRepository $autorRepository): JsonResponse
    {
        $json_autor = json_decode($request->getContent(), true);
        $autor = $autorRepository->find($id);

        if (!$autor) {
            return $this->json(['error' => 'Autor no encontrado'], 404);
        }

        if (!isset($json_autor['nombre'], $json_autor['apellidos'], $json_autor['biografia'], $json_autor['nacionalidad'], $json_autor['fecha_nacimiento'])) {
            return $this->json(['error' => 'Faltan datos obligatorios'], 400);
        }

        $fechaNacimiento = \DateTime::createFromFormat('Y-m-d', $json_autor['fecha_nacimiento']);
        if (!$fechaNacimiento) {
            return $this->json(['error' => 'Formato de fecha incorrecto'], 400);
        }

        $autor->setNombre($json_autor['nombre']);
        $autor->setApellidos($json_autor['apellidos']);
        $autor->setBiografia($json_autor['biografia']);
        $autor->setNacionalidad($json_autor['nacionalidad']);
        $autor->setFechaNacimiento($fechaNacimiento);

        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos actualizados correctamente']);
    }

    #[Route('/{id}', name: 'autor_delete_by_id', methods: ['DELETE'])]
    public function deleteById(Autor $autor, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($autor);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos eliminados correctamente']);
    }
}
