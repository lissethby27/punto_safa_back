<?php


namespace App\Controller;
use App\Entity\Autor;
use App\Entity\Categoria;
use App\Entity\Libro;
use App\Entity\LineaPedido;
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


    /**
     *
     * Crear un nuevo libro.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/guardar', name: 'guardar_libro', methods: ['POST'])]
    public function crearLibro(Request $request): JsonResponse
    {
        // Decodificar el JSON del request
        $datosLibro = json_decode($request->getContent(), true);

        // Validar los datos del libro
        if (strlen($datosLibro['titulo']) > 255) {
            return new JsonResponse(['mensaje' => 'El título no puede tener más de 255 caracteres'], 400);
        }

        // Validar que el precio sea un número positivo
        if (!is_numeric($datosLibro['precio']) || $datosLibro['precio'] < 0) {
            return new JsonResponse(['mensaje' => 'El precio debe ser un número positivo'], 400);
        }

        // Validar que la fecha de publicación tenga el formato correcto
        if (!\DateTime::createFromFormat('Y-m-d', $datosLibro['anioPublicacion'])) {
            return new JsonResponse(['mensaje' => 'Formato de fecha inválido, use YYYY-MM-DD'], 400);
        }

        // Validar el formato del ISBN
        if (!preg_match('/^\d{10}(\d{3})?$/', str_replace('-', '', $datosLibro['ISBN']))) {
            return new JsonResponse(['mensaje' => 'Formato de ISBN inválido'], 400);
        }

        // Validar que el ISBN no esté en uso
        $libro = $this->libroRepository->findOneBy(['ISBN' => $datosLibro['ISBN']]);
        if ($libro) {
            return new JsonResponse(['mensaje' => 'El ISBN ya está en uso'], 400);
        }

        // Validar que el resumen no tenga más de 800 caracteres
        if (strlen($datosLibro['resumen']) > 800) {
            return new JsonResponse(['mensaje' => 'El resumen no puede tener más de 800 caracteres'], 400);
        }

        // Crear un nuevo libro con los datos proporcionados
        $libro = new Libro();
        $libro->setTitulo($datosLibro['titulo'])
            ->setResumen($datosLibro['resumen'] ?? null)
            ->setAnioPublicacion(\DateTime::createFromFormat('Y-m-d', $datosLibro['anioPublicacion']) ?: new \DateTime())
            ->setPrecio($datosLibro['precio'])
            ->setISBN($datosLibro['ISBN'])
            ->setEditorial($datosLibro['editorial'])
            ->setImagen($datosLibro['imagen'] ?? null)
            ->setIdioma($datosLibro['idioma'] ?? null)
            ->setNumPaginas($datosLibro['numPaginas'] ?? null);


        // Obtener el Autor y Categoria desde el repositorio para asignarlos al libro (si existen)
        $autor = $this->autorRepository->find($datosLibro['autor']);
        $categoria = $this->categoriaRepository->find($datosLibro['categoria']);

        // Validar que el Autor y la Categoria existan
        if (!$autor || !$categoria) {
            return new JsonResponse(['mensaje' => 'Autor o categoría no encontrados'], 400);
        }

        // Asignar el Autor y la Categoria al libro
        $libro->setAutor($autor);
        $libro->setCategoria($categoria);

        // Guardar el libro en la base de datos
        $this->entityManager->persist($libro);
        $this->entityManager->flush();

        return new JsonResponse(['mensaje' => 'Libro guardado correctamente'], 201);
    }


    /**
     *
     * Listar todos los libros.
     *
     * @param Request $request
     * @param LibroRepository $libroRepository
     * @return JsonResponse
     */

    #[Route('/all', name: 'all_libros', methods: ['GET'])]
    public function listarLibros(Request $request, LibroRepository $libroRepository): JsonResponse
    {
        // Obtener parámetros de paginación que es opcional y por defecto es 1 y 10 respectivamente
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 9);


        // Crear consulta personalizada para evitar referencias circulares y mostrar datos relacionados de forma más clara
        $query = $libroRepository->createQueryBuilder('l')
            ->getQuery();


        // Paginar resultados y serializar a JSON con opciones personalizadas para evitar referencias circulares y mostrar datos relacionados de forma más clara
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        // Convertir el paginador a un array
        $listaLibros = iterator_to_array($paginator);

        // Serializar los libros a JSON y devolverlos como respuesta HTTP
        $jsonLibros = $this->serializer->serialize($listaLibros, 'json', [
            AbstractNormalizer::CALLBACKS => [
                'categoria' => fn($innerObject) => $innerObject ? $innerObject->getNombre() : null,
                'autor' => fn($innerObject) => $innerObject ? [


                    'nombre' => $innerObject->getNombre(),


                    'apellidos' => $innerObject->getApellidos(),


                ] : null,
                'anioPublicacion' => fn($object) => $object instanceof \DateTimeInterface ? $object->format('Y-m-d') : null,
                'lineaPedidos' => fn($innerObject) => null,
            ],
            'circular_reference_handler' => fn($object) => $object->getId(),
        ]);


        return new JsonResponse($jsonLibros, 200, [], true);
    }


    /**
     *
     * Buscar libros por rango de precios.
     *
     * @param LibroRepository $libroRepository
     * @param string $ranges
     * @return JsonResponse
     */

    #[Route('/precio/{ranges}', name: 'libros_by_precio', methods: ['GET'])]
    public function getLibrosByPrecio(LibroRepository $libroRepository, string $ranges): JsonResponse {
        // Convertir la cadena de rangos en un array
        $rangesArray = explode(',', $ranges);
        $queryBuilder = $libroRepository->createQueryBuilder('l');

        // Crear condiciones y parámetros para cada rango
        $conditions = [];
        $parameters = [];


        // Iterar sobre cada rango y agregar la condición correspondiente
        foreach ($rangesArray as $index => $range) {
            switch ($range) {
                case 'menor5':
                    $conditions[] = 'l.precio < :precio' . $index;
                    $parameters['precio' . $index] = 5;
                    break;
                case '5-10':
                    $conditions[] = 'l.precio BETWEEN :min' . $index . ' AND :max' . $index;
                    $parameters['min' . $index] = 5;
                    $parameters['max' . $index] = 10;
                    break;
                case '10-15':
                    $conditions[] = 'l.precio BETWEEN :min' . $index . ' AND :max' . $index;
                    $parameters['min' . $index] = 10;
                    $parameters['max' . $index] = 15;
                    break;
                case '15-40':
                    $conditions[] = 'l.precio BETWEEN :min' . $index . ' AND :max' . $index;
                    $parameters['min' . $index] = 15;
                    $parameters['max' . $index] = 40;
                    break;
                case 'mayor40':
                    $conditions[] = 'l.precio > :precio' . $index;
                    $parameters['precio' . $index] = 40;
                    break;
                default:
                    return new JsonResponse(['error' => 'Rango no válido'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Agregar las condiciones al QueryBuilder
        if (!empty($conditions)) {
            $queryBuilder->where(implode(' OR ', $conditions));
            foreach ($parameters as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }
        }

        // Ejecutar la consulta y devolver los resultados
        $listaLibros = $queryBuilder->getQuery()->getResult();

        return $this->json($listaLibros, Response::HTTP_OK, []);
    }


    /**
     *
     * Buscar libros por categoría.
     *
     * @param LibroRepository $libroRepository
     * @param CategoriaRepository $categoriaRepository
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */

    #[Route('/categoria/{id}', name: 'libros_by_categoria', methods: ['GET'])]
    public function getLibrosByCategoria(
        LibroRepository $libroRepository,
        CategoriaRepository $categoriaRepository,
        int $id,
        Request $request // Para obtener los parámetros de consulta
    ): JsonResponse {
        $categoria = $categoriaRepository->find($id);
        if (!$categoria) {
            return new JsonResponse(['error' => 'Categoría no encontrada'], Response::HTTP_NOT_FOUND);
        }

        // Obtener parámetros de paginación
        $page = $request->query->getInt('page', 1); // Página por defecto: 1
        $limit = $request->query->getInt('limit', 9); // Límite por defecto: 9

        // Calcular el offset
        $offset = ($page - 1) * $limit;

        // Obtener libros paginados
        $libros = $libroRepository->findBy(
            ['categoria' => $categoria],
            [], // Orden (opcional)
            $limit,
            $offset
        );

        return $this->json($libros, Response::HTTP_OK, []);
    }


    /**
     *
     * Buscar libros por autor.
     *
     * @param LibroRepository $libroRepository
     * @param AutorRepository $autorRepository
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */

    #[Route('/search', name: 'search_libros', methods: ['GET'])]
    public function searchLibros(
        Request $request,
        LibroRepository $libroRepository
    ): JsonResponse {

        // Obtener el término de búsqueda
        $query = $request->query->get('q');

        // Validar que se haya proporcionado un término de búsqueda
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


        return $this->json($libros, JsonResponse::HTTP_OK, []);

    }


    /**
     *
     * Filtrar libros por categoría y rango de precios.
     *
     * @param Request $request
     * @param LibroRepository $libroRepository
     * @param CategoriaRepository $categoriaRepository
     * @return JsonResponse
     */


    #[Route('/filtered-books', name: 'filtered_books', methods: ['GET'])]
    public function getFilteredBooks(
        Request $request,
        LibroRepository $libroRepository,
        CategoriaRepository $categoriaRepository
    ): JsonResponse {
        $categoryId = $request->query->get('categoryId'); // Obtener ID de la categoría
        $priceRanges = $request->query->get('priceRanges'); // Obtener rangos de precios como cadena
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 9);

        $minPrice = null;
        $maxPrice = null;

        // Validar que la categoría exista
        if($priceRanges){
            switch ($priceRanges) {
                case 'menor5':
                    $minPrice = 0;
                    $maxPrice = 5;
                    break;
                case "5-10":
                    $minPrice = 5;
                    $maxPrice = 10;
                    break;
                case "10-15":
                    $minPrice = 10;
                    $maxPrice = 15;
                    break;
                case "15-40":
                    $minPrice = 15;
                    $maxPrice = 40;
                    break;
                case "mayor40":
                    $minPrice = 40;
                    $maxPrice = 9999;
                    break;
            }
        }

       // Validar que la categoría exista
        $libros = $libroRepository->findLibrosByFiltro($categoryId, $minPrice, $maxPrice);


        return $this->json($libros, Response::HTTP_OK, []);
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
            if (isset($editarLibro['anioPublicacion'])) {
                $fechaPublicacion = \DateTime::createFromFormat('Y-m-d', $editarLibro['anioPublicacion']);
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
            if (isset($editarLibro['numPaginas'])) {
                $libro->setNumPaginas($editarLibro['numPaginas']);
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

        $lineaPedidoRepository = $entityManager->getRepository(LineaPedido::class);
        $lineasPedido = $lineaPedidoRepository->findBy(['libro' => $libro]);


//        if (!$this->isGranted('ROLE_ADMIN')) {
//            return new JsonResponse(['mensaje' => 'No tienes permisos para realizar esta acción'], 403);
//        }

        // If there are no line items, proceed to delete the book
        if (count($lineasPedido) == 0) {
            $titulo = $libro->getTitulo();
            $entityManager->remove($libro);
            $entityManager->flush();
            return new JsonResponse(['mensaje' => "Libro '$titulo' eliminado correctamente"], 200);
        }

        foreach ($lineasPedido as $linea) {
            $pedido = $linea->getPedido();  // Get the associated Pedido (order)

            // If this is the only LineaPedido in the Pedido, delete the entire Pedido
            if (count($pedido->getLineaPedidos()) == 1) {
                $entityManager->remove($pedido);  // Remove the entire order if it's the last line item
            }

            // Remove the specific LineaPedido associated with the book
            $entityManager->remove($linea);
        }
        $titulo = $libro->getTitulo();
        $entityManager->flush();

        return new JsonResponse(['mensaje' => "Libro '$titulo' eliminado correctamente"], 200);
    }


}

