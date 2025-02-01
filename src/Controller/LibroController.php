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
use App\Repository\AutorRepository;
use App\Repository\CategoriaRepository;


#[Route('/libro')]
class LibroController extends AbstractController
{
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private LibroRepository $libroRepository;
    private AutorRepository $autorRepository;
    private CategoriaRepository $categoriaRepository;

    // Constructor correctamente definido
    public function __construct(
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        LibroRepository $libroRepository,
        AutorRepository $autorRepository,
        CategoriaRepository $categoriaRepository
    ) {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->libroRepository = $libroRepository;
        $this->autorRepository = $autorRepository;
        $this->categoriaRepository = $categoriaRepository;
    }





    //Guardar libro en la base de datos
    #[Route('/guardar', name: 'guardar_libro', methods: ['POST'])]
    public function crearLibro(Request $request): JsonResponse
    {
        // Obtener datos del JSON
        $datosLibro = json_decode($request->getContent(), true);

        // Validar datos
        if (!isset($datosLibro['titulo'], $datosLibro['precio'], $datosLibro['ISBN'], $datosLibro['editorial'], $datosLibro['autor'], $datosLibro['categoria'], $datosLibro['anio_publicacion'], $datosLibro['num_paginas'], $datosLibro['imagen'], $datosLibro['idioma'], $datosLibro['resumen'], $datosLibro['num_paginas'], $datosLibro['imagen'])) {
            return new JsonResponse(['mensaje' => 'Faltan datos obligatorios'], 400);
        }

        // Crear una nueva instancia del libro
        $libro = new Libro();
        $libro->setTitulo($datosLibro['titulo'])
            ->setResumen($datosLibro['resumen'] ?? null)
            ->setAnioPublicacion(\DateTime::createFromFormat('Y-m-d', $datosLibro['anio_publicacion']) ?: new \DateTime())
            ->setPrecio($datosLibro['precio'])
            ->setISBN($datosLibro['ISBN'])
            ->setEditorial($datosLibro['editorial'])
            ->setImagen($datosLibro['imagen'] ?? null)
            ->setIdioma($datosLibro['idioma'] ?? null)
            ->setNumPaginas($datosLibro['num_paginas'] ?? null);

        // Obtener el Autor desde el repositorio
        $autor = $this->autorRepository->find($datosLibro['autor']);
        if (!$autor) {
            return new JsonResponse(['mensaje' => 'Autor no encontrado'], 400);
        }
        $libro->setAutor($autor);

        // Obtener la Categoria desde el repositorio
        $categoria = $this->categoriaRepository->find($datosLibro['categoria']);
        if (!$categoria) {
            return new JsonResponse(['mensaje' => 'Categoría no encontrada'], 400);
        }
        $libro->setCategoria($categoria);

        // Persistir el libro en la base de datos
        $this->entityManager->persist($libro);
        $this->entityManager->flush();

        return new JsonResponse(['mensaje' => 'Libro guardado correctamente'], 201);
    }


    //Lista de todos los libros existentes en la base de datos
    #[Route('/all', name: 'all_libros', methods: ['GET'])]
    public function listarLibros(LibroRepository $libroRepository): JsonResponse
    {
        // Obtener todos los libros
        $listaLibros = $libroRepository->findAll();

        // Serializar los libros a JSON y devolverlos como respuesta HTTP
        $jsonLibros = $this->serializer->serialize($listaLibros, 'json', [
            AbstractNormalizer::CALLBACKS => [
                'categoria' => fn($innerObject) => $innerObject ? $innerObject->getNombre() : null,
                'autor' => fn($innerObject) => $innerObject ? $innerObject->getNombre() . ' ' . $innerObject->getApellidos() : null,
                'anioPublicacion' => fn($object) => $object instanceof \DateTimeInterface ? $object->format('Y-m-d') : null, // Formatear fecha de publicación
                'lineaPedidos' => fn($innerObject) => null, // Evitar serialización de LineaPedido
            ],
            // Evitar referencias circulares en la serialización
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($jsonLibros, 200, [], true);
    }






    //Listar un libro por su id
    #[Route('/{id}', name: 'libro_by_id', methods: ['GET'])]
    public function getById(Libro $libro): JsonResponse
    {
        if (!$libro) {
            return $this->json(['mensaje' => 'Libro no encontrado'], 404);
        }

        $json = $this->serializer->serialize($libro, 'json', [
            AbstractNormalizer::CALLBACKS => [
                'anioPublicacion' => fn($object) => $object instanceof \DateTimeInterface ? $object->format('Y-m-d') : null, // Formatear fecha de publicación

                'categoria' => function ($innerObject) {
                    return $innerObject ? $innerObject->getNombre() : null;
                },
                'autor' => function ($innerObject) {
                    return $innerObject ? $innerObject->getNombre() . ' ' . $innerObject->getApellidos() : null;

                },
                'lineaPedidos' => function ($innerObject) {
                    return 'Dato restringido';                }, // Evitar serialización de LineaPedido. Queda pendiente de resolver la duda, po rposible requirimiento de la app



            ],
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($json, 200, [], true);
    }



    #[Route('/editar/{id}', name: 'editar_libro', methods: ['PUT'])]
    public function editar(
        int $id,
        Request $request
    ): JsonResponse {
        $libro = $this->entityManager->getRepository(Libro::class)->find($id);

        if (!$libro) {
            return new JsonResponse(['mensaje' => 'Libro no encontrado, inténtelo de nuevo'], 404);
        }

        $editarLibro = json_decode($request->getContent(), true);

        try {
            // Editar título
            if (isset($editarLibro['titulo'])) {
                $libro->setTitulo($editarLibro['titulo']);
            }

            // Editar resumen
            if (isset($editarLibro['resumen'])) {
                $libro->setResumen($editarLibro['resumen']);
            }

            // Editar año de publicación (con validación)
            if (isset($editarLibro['anio_publicacion'])) {
                $fechaPublicacion = \DateTime::createFromFormat('Y-m-d', $editarLibro['anio_publicacion']);
                if ($fechaPublicacion === false) {
                    return new JsonResponse(['mensaje' => 'Formato de fecha inválido, use YYYY-MM-DD'], 400);
                }
                $libro->setAnioPublicacion($fechaPublicacion);
            }

            // Editar precio
            if (isset($editarLibro['precio'])) {
                $libro->setPrecio($editarLibro['precio']);
            }

            // Editar ISBN
            if (isset($editarLibro['ISBN'])) {
                $libro->setISBN($editarLibro['ISBN']);
            }

            // Editar editorial
            if (isset($editarLibro['editorial'])) {
                $libro->setEditorial($editarLibro['editorial']);
            }

            // Editar imagen
            if (isset($editarLibro['imagen'])) {
                $libro->setImagen($editarLibro['imagen']);
            }

            // Editar idioma
            if (isset($editarLibro['idioma'])) {
                $libro->setIdioma($editarLibro['idioma']);
            }

            // Editar número de páginas
            if (isset($editarLibro['num_paginas'])) {
                $libro->setNumPaginas($editarLibro['num_paginas']);
            }

            // Manejar la relación con Autor
            if (isset($editarLibro['autor'])) {
                $autor = $this->autorRepository->find($editarLibro['autor']);
                if (!$autor) {
                    return new JsonResponse(['mensaje' => 'Autor no encontrado'], 400);
                }
                $libro->setAutor($autor);
            }

            // Manejar la relación con Categoria
            if (isset($editarLibro['categoria'])) {
                $categoria = $this->categoriaRepository->find($editarLibro['categoria']);
                if (!$categoria) {
                    return new JsonResponse(['mensaje' => 'Categoría no encontrada'], 400);
                }
                $libro->setCategoria($categoria);
            }

            // Guardar cambios
            $this->entityManager->flush();

            return new JsonResponse(['mensaje' => 'Libro actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error interno del servidor', 'detalle' => $e->getMessage()], 500);
        }
    }



    //Eliminar un libro por su id
    #[Route('/eliminar/{id}', name: 'eliminar_libro', methods: ['DELETE'])]
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