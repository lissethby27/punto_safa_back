<?php

namespace App\Controller;
use App\Entity\Libro;
use App\Entity\Usuario;
use App\Repository\LibroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/libro')]
final class LibroController extends AbstractController{
    private SerializerInterface $serializer;
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }


    //Guardar libro en la base de datos
    #[Route('/guardarLibro', name: 'guardar_libro', methods: ['POST'])]
    public function crearLibro(Request $request, EntityManagerInterface $entityManager, LibroRepository $libroRepository): JsonResponse
    {
        $json_libro = json_decode($request->getContent(), true);

        // Verificar si el ISBN ya existe
        $libroExistente = $libroRepository->findOneBy(['ISBN' => $json_libro['ISBN']]);
        if ($libroExistente) {
            return new JsonResponse(['mensaje' => 'El ISBN ya existe, por favor ingrese un ISBN diferente'], 400);
        }

        $nuevo_libro = new Libro();
        $nuevo_libro->setTitulo($json_libro['titulo']);
        $nuevo_libro->setResumen($json_libro['resumen']);
        $nuevo_libro->setAnioPublicacion($json_libro['anio_publicacion']);
        $nuevo_libro->setPrecio($json_libro['precio']);
        $nuevo_libro->setISBN($json_libro['ISBN']);
        $nuevo_libro->setEditorial($json_libro['editorial']);
        $nuevo_libro->setImagen($json_libro['imagen']);
        $nuevo_libro->setIdioma($json_libro['idioma']);
        $nuevo_libro->setNumPaginas($json_libro['num_paginas']);
        $nuevo_libro->setAutor($json_libro['autor']);
        $nuevo_libro->setCategoria($json_libro['categoria']);

        $entityManager->persist($nuevo_libro);
        $entityManager->flush();

        return new JsonResponse(['mensaje' => 'Libro registrado correctamente'], 201);
    }


    //Lista de todos los libros existentes en la base de datos



    #[Route('/all', name: 'all_libros', methods: ['GET'])]
    public function listarLibros(LibroRepository $libroRepository): JsonResponse
    {
        $listaLibros = $libroRepository->findAll();

        // Excluye propiedades problemáticas si es necesario
        $jsonLibros = $this->serializer->serialize($listaLibros, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['autor', 'categoria', 'lineaPedidos'],
            'datetime_format' => 'Y-m-d'
        ]);

        return new JsonResponse($jsonLibros, 200, [], true);
    }
    #[Route('/all', name: 'all_libros', methods: ['GET'])]
    public function listarLibros(LibroRepository $libroRepository): JsonResponse
    {
        $listaLibros = $libroRepository->findAll();

        dump($listaLibros); // <-- Verifica lo que devuelve
        die(); // <-- Detiene la ejecución para revisar el resultado

        return new JsonResponse([]);
    }







    //Listar un libro por su id
 #[Route('/{id}', name: 'libro_by_id', methods: ['GET'])]
    public function getById(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $libro = $entityManager->getRepository(Libro::class)->find($id);

        if (!$libro) {
            return new JsonResponse(['mensaje' => 'Libro no encontrado'], 404);
         }

        return $this->json($libro, 200, ["Libro encontrado"]);
    }


    //Editar un libro por su id
    #[Route('/editarLibro/{id}', name: 'editar_libro', methods: ['PUT'])]
    public function editarLibro(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $libro = $entityManager->getRepository(Libro::class)->find($id);

        if (!$libro) {
            return new JsonResponse(['mensaje' => 'Libro no encontrado, inténtelo de nuevo'], 404);
        }

        $editarLibro = json_decode($request -> getContent(), true);

        $libro -> setTitulo($editarLibro['titulo']);
        $libro -> setResumen($editarLibro['resumen']);
        $libro -> setAnioPublicacion($editarLibro['anio_publicacion']);
        $libro -> setPrecio($editarLibro['precio']);
        $libro -> setISBN($editarLibro['ISBN']);
        $libro -> setEditorial($editarLibro['editorial']);
        $libro -> setImagen($editarLibro['imagen']);
        $libro -> setIdioma($editarLibro['idioma']);
        $libro -> setNumPaginas($editarLibro['num_paginas']);
        $libro -> setAutor($editarLibro['autor']);
        $libro -> setCategoria($editarLibro['categoria']);

        $entityManager->flush();

        return new JsonResponse(['mensaje' => 'Libro actualizado correctamente'], 200);


    }

    //Eliminar un libro por su id
    #[Route('/eliminarLibro/{id}', name: 'eliminar_libro', methods: ['DELETE'])]
    public function eliminarLibro(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $libro = $entityManager->getRepository(Libro::class)->find($id);

        if (!$libro) {
            return new JsonResponse(['mensaje' => 'Libro no encontrado, inténtelo de nuevo'], 404);
        }

        $entityManager->remove($libro);
        $entityManager->flush();

        return new JsonResponse(['mensaje' => 'Libro eliminado correctamente'], 200);
    }

}