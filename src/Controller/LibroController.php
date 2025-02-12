<?php

namespace App\Controller;
use App\Entity\Libro;
use App\Repository\LibroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        if (!isset($datosLibro['titulo'], $datosLibro['precio'], $datosLibro['ISBN'], $datosLibro['editorial'],
            $datosLibro['autor'], $datosLibro['categoria'], $datosLibro['anio_publicacion'],
            $datosLibro['num_paginas'], $datosLibro['imagen'], $datosLibro['idioma'], $datosLibro['resumen'])) {
            return new JsonResponse(['mensaje' => 'Faltan datos obligatorios'], 400);
        }

        //Validar el título no puedde tener más de 255 caracteres
        if (strlen($datosLibro['titulo']) > 255) {
            return new JsonResponse(['mensaje' => 'El título no puede tener más de 255 caracteres'], 400);
        }

        // Validar que el precio es un número positivo
        if (!is_numeric($datosLibro['precio']) || $datosLibro['precio'] < 0) {
            return new JsonResponse(['mensaje' => 'El precio debe ser un número positivo'], 400);
        }

        // Validar que el año de publicación es una fecha válida
        if (!\DateTime::createFromFormat('Y-m-d', $datosLibro['anio_publicacion'])) {
            return new JsonResponse(['mensaje' => 'Formato de fecha inválido, use YYYY-MM-DD'], 400);
        }


        // Validar el ISBN (longitud y solo números o guiones)
        if (!preg_match('/^\d{10}(\d{3})?$/', str_replace('-', '', $datosLibro['ISBN']))) {
            return new JsonResponse(['mensaje' => 'Formato de ISBN inválido'], 400);
        }

        //Validar que el ISB no exista en la base de datos
        $libro = $this->libroRepository->findOneBy(['ISBN' => $datosLibro['ISBN']]);
        if ($libro) {
            return new JsonResponse(['mensaje' => 'El ISBN ya está en uso'], 400);
        }

        //Máximo de caracteres en el resumen de 800
        if (strlen($datosLibro['resumen']) > 800) {
            return new JsonResponse(['mensaje' => 'El resumen no puede tener más de 800 caracteres'], 400);
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

        // Obtener el Autor y Categoria desde el repositorio para asignarlos al libro (si existen)
        $autor = $this->autorRepository->find($datosLibro['autor']);
        $categoria = $this->categoriaRepository->find($datosLibro['categoria']);

        if (!$autor || !$categoria) {
            return new JsonResponse(['mensaje' => 'Autor o categoría no encontrados'], 400);
        }

        $libro->setAutor($autor);
        $libro->setCategoria($categoria);

//        if (!$this->isGranted('ROLE_ADMIN')) {
//            return new JsonResponse(['mensaje' => 'No tienes permisos para realizar esta acción'], 403);
//        }


        // Persistir el libro en la base de datos
        $this->entityManager->persist($libro);
        $this->entityManager->flush();


        return new JsonResponse(['mensaje' => 'Libro guardado correctamente'], 201);
    }


    //Lista de todos los libros existentes en la base de datos
    //Código optimizado para evitar referencias circulares y mostrar datos relacionados de forma más clara
    //La idea es que en una misma página no se cargue todos los libros, sino que se paginen, así no es infinita la carga de libros
    #[Route('/all', name: 'all_libros', methods: ['GET'])]
    public function listarLibros(Request $request, LibroRepository $libroRepository): JsonResponse
    {
        // Obtener parámetros de paginación que es opcional y por defecto es 1 y 10 respectivamente
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        // Crear consulta personalizada para evitar referencias circulares y mostrar datos relacionados de forma más clara
        $query = $libroRepository->createQueryBuilder('l')
            ->getQuery();

        // Paginar resultados y serializar a JSON con opciones personalizadas para evitar referencias circulares y mostrar datos relacionados de forma más clara
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        $listaLibros = iterator_to_array($paginator);

        $jsonLibros = $this->serializer->serialize($listaLibros, 'json', [
            AbstractNormalizer::CALLBACKS => [
                'categoria' => fn($innerObject) => $innerObject ? $innerObject->getNombre() : null,
                'autor' => fn($innerObject) => $innerObject ? $innerObject->getNombre() . ' ' . $innerObject->getApellidos() : null,
                'anioPublicacion' => fn($object) => $object instanceof \DateTimeInterface ? $object->format('Y-m-d') : null,
                'lineaPedidos' => fn($innerObject) => null,
            ],
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);

        return new JsonResponse($jsonLibros, 200, [], true);
    }

    #[Route('/precio/{range}', name: 'libros_by_precio', methods: ['GET'])]
    public function getLibrosByPrecio(LibroRepository $libroRepository, string $range): JsonResponse{
        switch ($range) {
            case 'menor5':
                $listaLibros = $libroRepository->createQueryBuilder('l')
                    ->where('l.precio < :precio')
                    ->setParameter('precio', 5)
                    ->getQuery()
                    ->getResult();
                break;
                case '5-10':
                    $listaLibros = $libroRepository->createQueryBuilder('l')
                        ->where('l.precio BETWEEN :min AND :max')
                        ->setParameter('min', 5)
                        ->setParameter('max', 10)
                        ->getQuery()
                        ->getResult();
                    break;
                    case '10-15':
                        $listaLibros = $libroRepository->createQueryBuilder('l')
                            ->where('l.precio BETWEEN :min AND :max')
                            ->setParameter('min', 10)
                            ->setParameter('max', 15)
                            ->getQuery()
                            ->getResult();
                        break;
                        case '15-40':
                            $listaLibros = $libroRepository->createQueryBuilder('l')
                                ->where('l.precio BETWEEN :min AND :max')
                                ->setParameter('min', 15)
                                ->setParameter('max', 40)
                                ->getQuery()
                                ->getResult();
                            break;
                            case 'mayor40':
                                $listaLibros = $libroRepository->createQueryBuilder('l')
                                    ->where('l.precio> :precio')
                                    ->setParameter('precio', 40)
                                    ->getQuery()
                                    ->getResult();
                                break;
            default: return new JsonResponse(['error' => 'Rango no válido'], Response::HTTP_BAD_REQUEST);
        }
        return $this->json($listaLibros, Response::HTTP_OK, [], ['groups' => ['libro_list']]);

    }


    //Filtro de libros por categoría para obtener los libros de una categoría específica


    // Filtro de libros por categoria (id)
    #[Route('/categoria/{id}', name: 'libros_by_categoria', methods: ['GET'])]
    public function getLibrosByCategoria(LibroRepository $libroRepository, CategoriaRepository $categoriaRepository ,string $id): JsonResponse{
        // Buscar la categoría por su ID
        $categoria = $categoriaRepository->find($id);

        if(!$categoria){
            return new JsonResponse(['error' => 'Categoría no encontrada'], Response::HTTP_NOT_FOUND);
        }

        // Buscar libros que pertenezcan a la categoría
        $libros = $libroRepository->findBy(['categoria' => $categoria]);


        return $this->json($libros, Response::HTTP_OK, []);

    }

    #[Route('/search', name: 'search_libros', methods: ['GET'])]
    public function searchLibros(
        Request $request,
        LibroRepository $libroRepository
    ): JsonResponse {
        // Get the search query from the request
        $query = $request->query->get('q');

        if (!$query) {
            return new JsonResponse(['error' => 'Debe proporcionar un término de búsqueda'], Response::HTTP_BAD_REQUEST);
        }

        // Search for libros by title or author
        $libros = $libroRepository->createQueryBuilder('l')
            ->leftJoin('l.autor', 'a') // Join with Autor
            ->where('l.titulo LIKE :query')
            ->orWhere('a.nombre LIKE :query')
            ->orWhere('a.apellidos LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();

        return $this->json($libros, JsonResponse::HTTP_OK, [], ['groups' => ['libro_list']]);

    }




    //Llamar a  un libro por su id
    #[Route('/{id}', name: 'libro_by_id', methods: ['GET'])]
    public function getById(Libro $libro): JsonResponse
    {
        // Serializar el libro a JSON y devolverlo como respuesta HTTP (si existe) o un mensaje de error
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
    public function editar(int $id, Request $request): JsonResponse
    {
        // Buscar el libro en la base de datos
        $libro = $this->entityManager->getRepository(Libro::class)->find($id);
        if (!$libro) {
            return new JsonResponse(['mensaje' => 'Libro no encontrado, inténtelo de nuevo'], 404);
        }

        // Decodificar el JSON del request
        $editarLibro = json_decode($request->getContent(), true);

        try {
            // Editar título
            if (isset($editarLibro['titulo'])) {
                if (strlen($editarLibro['titulo']) > 255) {
                    return new JsonResponse(['mensaje' => 'El título no puede tener más de 255 caracteres'], 400);
                }
                $libro->setTitulo($editarLibro['titulo']);
            }

            // Editar resumen
            if (isset($editarLibro['resumen'])) {
                if (strlen($editarLibro['resumen']) > 800) {
                    return new JsonResponse(['mensaje' => 'El resumen no puede tener más de 800 caracteres'], 400);
                }
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

            // Editar precio (con validación)
            if (isset($editarLibro['precio']) && is_numeric($editarLibro['precio']) && $editarLibro['precio'] > 0) {
                $libro->setPrecio($editarLibro['precio']);
            }


            // Editar ISBN (con validación)
            if (isset($editarLibro['ISBN'])) {
                $existingLibro = $this->libroRepository->findOneBy(['ISBN' => $editarLibro['ISBN']]);
                if ($existingLibro && $existingLibro->getId() !== $libro->getId()) {
                    return new JsonResponse(['mensaje' => 'El ISBN ya está en uso por otro libro'], 400);
                }
                if (!preg_match('/^\d{10}(\d{3})?$/', str_replace('-', '', $editarLibro['ISBN']))) {
                    return new JsonResponse(['mensaje' => 'Formato de ISBN inválido'], 400);
                }
                $libro->setISBN($editarLibro['ISBN']);
            }

            // Editar editorial
            if (isset($editarLibro['editorial'])) {
                $libro->setEditorial($editarLibro['editorial']);
            }

            // Editar imagen (esto puede depender de cómo se gestione la subida de archivos)
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
                // Buscar el autor por el ID
                $autor = $this->autorRepository->find($editarLibro['autor']);
                if (!$autor) {
                    return new JsonResponse(['mensaje' => 'Autor no encontrado'], 400);
                }
                // Asignar el objeto Autor al libro
                $libro->setAutor($autor);
            }

            // Manejar la relación con Categoria
            if (isset($editarLibro['categoria'])) {
                // Buscar la categoría por el ID
                $categoria = $this->categoriaRepository->find($editarLibro['categoria']);
                if (!$categoria) {
                    return new JsonResponse(['mensaje' => 'Categoría no encontrada'], 400);
                }
                // Asignar el objeto Categoria al libro
                $libro->setCategoria($categoria);
            }
//            if (!$this->isGranted('ROLE_ADMIN')) {
//                return new JsonResponse(['mensaje' => 'No tienes permisos para realizar esta acción'], 403);
//        }



            // Guardar los cambios en la base de datos
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

        $lineasPedido = $libro->getLineaPedidos();
        if (count($lineasPedido) > 0) {
            return new JsonResponse(['mensaje' => 'No se puede eliminar un libro con pedidos activos'], 400);
        }

//        if (!$this->isGranted('ROLE_ADMIN')) {
//            return new JsonResponse(['mensaje' => 'No tienes permisos para realizar esta acción'], 403);
//        }





        $titulo = $libro->getTitulo();
        $entityManager->remove($libro);
        $entityManager->flush();

        return new JsonResponse(['mensaje' => "Libro '$titulo' eliminado correctamente"], 200);

    }

}