<?php

namespace App\Controller;

use App\Entity\Resena;
use App\Repository\LibroRepository;
use App\Repository\ResenaRepository;
use App\Repository\UsuarioRepository;
use App\Repository\ClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
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
    private ClienteRepository $clienteRepository;
    private EntityManagerInterface $entityManager;
    private JWSProviderInterface $jwsProvider;

    public function __construct(
        ResenaRepository $resenaRepository,
        UsuarioRepository $usuarioRepository,
        LibroRepository $libroRepository,
        ClienteRepository $clienteRepository,
        EntityManagerInterface $entityManager,
        JWSProviderInterface $jwsProvider
    ) {
        $this->resenaRepository = $resenaRepository;
        $this->usuarioRepository = $usuarioRepository;
        $this->libroRepository = $libroRepository;
        $this->clienteRepository = $clienteRepository;
        $this->entityManager = $entityManager;
        $this->jwsProvider = $jwsProvider;
    }

    #[Route('/nueva', name: 'app_resena_nuevaresena', methods: ['POST'])]
    public function nuevaResena(Request $request): JsonResponse
    {
        // Obtener el token del encabezado de autorización
        $token = $request->headers->get('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return new JsonResponse(['mensaje' => 'Token no proporcionado.'], Response::HTTP_UNAUTHORIZED);
        }

        // Eliminar el prefijo 'Bearer ' del token
        $token = str_replace('Bearer ', '', $token);

        try {
            // Decodificar el token usando JWSProviderInterface
            $jws = $this->jwsProvider->load($token);

            if (!$jws->isVerified()) {
                return new JsonResponse(['mensaje' => 'Token inválido.'], Response::HTTP_UNAUTHORIZED);
            }

            // Obtener el payload del token
            $decodedToken = $jws->getPayload();

            // Verificar que el token decodificado sea un array válido
            if (!isset($decodedToken['username'])) {
                return new JsonResponse(['mensaje' => 'Token inválido.'], Response::HTTP_UNAUTHORIZED);
            }

            // Obtener el nick del usuario del token decodificado
            $nick = $decodedToken['username'];

            // Obtener usuario autenticado por su nick
            $usuario = $this->usuarioRepository->findOneByNick($nick);
            if (!$usuario) {
                return new JsonResponse(['mensaje' => 'Usuario no encontrado.'], Response::HTTP_UNAUTHORIZED);
            }

            // 1. Obtener y validar datos de la reseña
            $dataResena = json_decode($request->getContent(), true);
            if (!isset($dataResena['libro'], $dataResena['calificacion'], $dataResena['comentario'])) {
                return new JsonResponse(['mensaje' => 'Datos incompletos.'], Response::HTTP_BAD_REQUEST);
            }

            // 2. Validar el libro
            $libro = $this->libroRepository->find($dataResena['libro']);
            if (!$libro) {
                return new JsonResponse(['mensaje' => 'Libro no encontrado.'], Response::HTTP_NOT_FOUND);
            }

            // 3. Obtener el cliente asociado al usuario
            $cliente = $usuario->getCliente();
            if (!$cliente) {
                return new JsonResponse(['mensaje' => 'No tienes permisos para hacer una reseña.'], Response::HTTP_FORBIDDEN);
            }



            // 4. Verificar si el usuario ha comprado el libro HASTA AQUI PETA
            if (!$this->verificarCompra($cliente->getId(), $libro->getId())) {
                return new JsonResponse(['mensaje' => 'Debes comprar el libro antes de hacer una reseña.'], Response::HTTP_FORBIDDEN);
            }


            //5.  Verificar si el usuario ya hizo una reseña para este libro
            if ($this->resenaRepository->findOneBy(['usuario' => $usuario, 'libro' => $libro])) {
                return new JsonResponse(['mensaje' => 'Solo puedes hacer una reseña por libro.'], Response::HTTP_CONFLICT);
            }



            // 6. Validar calificación
            if (!is_numeric($dataResena['calificacion']) || $dataResena['calificacion'] < 1 || $dataResena['calificacion'] > 5) {
                return new JsonResponse(['mensaje' => 'La calificación debe ser un número entre 1 y 5.'], Response::HTTP_BAD_REQUEST);
            }

            // 7. Validar comentario
            if (empty($dataResena['comentario']) || strlen($dataResena['comentario']) > 200) {
                return new JsonResponse(['mensaje' => 'El comentario no puede estar vacío y debe tener un máximo de 200 caracteres.'], Response::HTTP_BAD_REQUEST);
            }

            // Crear nueva reseña
            $nuevaResena = new Resena();
            $nuevaResena->setUsuario($usuario)
                ->setLibro($libro)
                ->setComentario($dataResena['comentario'])
                ->setFecha(new \DateTime())
                ->setCalificacion($dataResena['calificacion']);

            $this->entityManager->persist($nuevaResena);
            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $nuevaResena->getId(),
                'usuario' => $usuario->getId(),
                'libro' => $libro->getId(),
                'calificacion' => $nuevaResena->getCalificacion(),
                'comentario' => $nuevaResena->getComentario(),
                'fecha' => $nuevaResena->getFechaFormatted()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['mensaje' => 'Error al decodificar el token.'], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Verifica si un cliente ha comprado un libro.
     */
    private function verificarCompra(int $clienteId, int $libroId): bool
    {
        $query = $this->entityManager->createQuery(
            'SELECT COUNT(lp.id) 
         FROM App\Entity\LineaPedido lp
         JOIN lp.pedido p
         WHERE p.cliente = :clienteId AND lp.libro = :libroId'
        )->setParameters([
            'clienteId' => $clienteId,
            'libroId' => $libroId
        ]);

        // Depurar el SQL generado
        $sql = $query->getSQL();
        echo "SQL: $sql\n"; // Imprime el SQL en la consola o logs

        return $query->getSingleScalarResult() > 0;
    }

    // Método para actualizar una reseña
    #[Route("/actualizar/{id}", name: "actualizar_resena", methods: ["PUT"])]
    public function editar(int $id, Request $request): JsonResponse
    {
        $resena = $this->resenaRepository->find($id);
        if (!$resena) {
            return new JsonResponse(['mensaje' => 'Reseña no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $dataResena = json_decode($request->getContent(), true);
        if (isset($dataResena['calificacion']) && (!is_numeric($dataResena['calificacion']) || $dataResena['calificacion'] < 1 || $dataResena['calificacion'] > 5)) {
            return new JsonResponse(['mensaje' => 'Calificación inválida.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($dataResena['comentario']) && (empty($dataResena['comentario']) || strlen($dataResena['comentario']) > 200)) {
            return new JsonResponse(['mensaje' => 'Comentario inválido.'], Response::HTTP_BAD_REQUEST);
        }

        $resena->setCalificacion($dataResena['calificacion'] ?? $resena->getCalificacion())
            ->setComentario($dataResena['comentario'] ?? $resena->getComentario())
            ->setFecha(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse(['mensaje' => 'Reseña actualizada.'], Response::HTTP_OK);
    }

    // Método para ver las reseñas de un libro
    #[Route("/resenas/{id_libro}", name: "mostrar_resenas_libro", methods: ["GET"])]
    public function mostrarResenasPorLibro(int $id_libro): JsonResponse
    {
        $resenas = $this->resenaRepository->findBy(['libro' => $id_libro]);

        if (!$resenas) {
            return new JsonResponse(['mensaje' => 'No hay reseñas para este libro.'], Response::HTTP_OK);
        }

        // Formateamos las reseñas para enviarlas en JSON
        $resenasArray = array_map(function ($resena) {
            return [
                'id'           => $resena->getId(),
                'usuario'      => $resena->getUsuario()->getNick(),
                'libro'        => $resena->getLibro()->getId(),
                'calificacion' => $resena->getCalificacion(),
                'comentario'   => $resena->getComentario(),
                'fecha'        => $resena->getFechaFormatted()
            ];
        }, $resenas);

        return new JsonResponse($resenasArray, Response::HTTP_OK);
    }

    // Método para calcular la media de calificaciones de un libro
    #[Route("/media-calificacion/{id_libro}", name: "media_calificacion_libro", methods: ["GET"])]
    public function mediaCalificacionPorLibro(int $id_libro): JsonResponse
    {
        $media = $this->resenaRepository->calcularMediaCalificacionPorLibro($id_libro);

        if ($media === null) {
            return new JsonResponse(['mensaje' => 'No hay reseñas para este libro.'], Response::HTTP_OK);
        }

        return new JsonResponse(['mediaCalificacion' => $media], Response::HTTP_OK);
    }

    // Método para obtener los libros mejor calificados
    #[Route("/top-libros", name: "top_libros", methods: ["GET"])]
    public function topLibros(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 3);

        if ($limit <= 0) {
            return new JsonResponse(['mensaje' => 'El límite debe ser un número positivo.'], Response::HTTP_BAD_REQUEST);
        }

        $topLibros = array_map(function ($libro) {
            $libro['mediaCalificacion'] = number_format((float)$libro['mediaCalificacion'], 1, '.', '');
            return $libro;
        }, $this->resenaRepository->findTopRatedBooks($limit));

        return new JsonResponse($topLibros, Response::HTTP_OK);
    }
}