<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Libro;
use App\Entity\LineaPedido;
use App\Entity\Pedido;
use App\Repository\PedidoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
//use Symfony\Component\Security\Core\Security;



#[Route('/pedido', name: 'app_pedido')]
final class PedidoController extends AbstractController
{

    /**
     *
     * Obtiene todos los pedidos.
     *
     * @param PedidoRepository $pedidoRepository
     * @param SerializerInterface $serializer
     * @return Response
     */
    #[Route('/all', name: 'get_all_pedidos', methods: ['GET'])]
    public function getAll(PedidoRepository $pedidoRepository, SerializerInterface $serializer): Response
    {
        // Obtener todos los pedidos
        $pedidos = $pedidoRepository->findAll();

        // Serializar los pedidos con un grupo definido
        $json = $serializer->serialize($pedidos, 'json', ['groups' => 'pedido:read']);

        return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }


    /**
     *
     * Actualiza el estado de un pedido.
     *
     * @param int $id
     * @param PedidoRepository $pedidoRepository
     * @param SerializerInterface $serializer
     * @return Response
     */

    #[Route('/{id}/estado', name: 'update_estado_pedido', methods: ['PUT'])]
    public function updateEstado(int $id,
                                 Request $request, PedidoRepository $pedidoRepository,
                                 SerializerInterface $serializer,
                                 EntityManagerInterface $entityManager): Response
    {

        // Encontrar el pedido por su ID
        $pedido = $pedidoRepository->find($id);
        if (!$pedido) {
            return $this->json(['message' => 'Pedido not found'], Response::HTTP_NOT_FOUND);
        }

        // Decodificar los datos JSON de la solicitud
        $data = json_decode($request->getContent(), true);

        // Validar si el campo estado está presente
        if (!isset($data['estado'])) {
            return new JsonResponse(['error' => 'New estado is required'], Response::HTTP_BAD_REQUEST);
        }

        // Actualizar el estado del pedido
        $pedido->setEstado($data['estado']);

        // Persistir los cambios en la base de datos
        $entityManager->persist($pedido);
        $entityManager->flush();

        // Serializar el pedido con un grupo definido
        $json = $serializer->serialize($pedido, 'json', ['groups' => 'pedido:read']);

        return $this->json([
            'message' => 'Estado updated successfully',
            'pedido' => [
                'id' => $pedido->getId(),
                'estado' => $pedido->getEstado(),
            ]
        ]);
    }

    /**
     *
     * Obtiene todos los pedidos pendientes.
     *
     * @param int $id
     * @param PedidoRepository $pedidoRepository
     * @param SerializerInterface $serializer
     * @return Response
     */

    #[Route('/all/pendientes', name: 'get_all_pedidos', methods: ['GET'])]
    public function getPendingPedidos(PedidoRepository $pedidoRepository, SerializerInterface $serializer): Response
    {
        // Obtener todos los pedidos que no han sido entregados
        $pedidos = $pedidoRepository->createQueryBuilder('p')
            ->leftJoin('p.cliente', 'c')  // Join Cliente entity
            ->addSelect('c') // Fetch Cliente data
            ->where('p.estado != :entregado')
            ->setParameter('entregado', 'entregado')
            ->getQuery()
            ->getResult();


        // Serializar los pedidos con un grupo definido
        $json = $serializer->serialize($pedidos, 'json', ['groups' => 'pedido:read']);

        return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }


    /**
     *
     * Guarda un nuevo pedido.
     *
     * @param int $id
     * @param PedidoRepository $pedidoRepository
     * @param SerializerInterface $serializer
     * @return Response
     */

    #[Route('/save', name: 'save_pedidos', methods: ['POST'])]
    public function savePedido(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        // Decodificar los datos JSON de la solicitud
        $json_pedido = json_decode($request->getContent(), true);

        // Validar si el campo cliente está presente
        $pedido = new Pedido();
        $pedido->setFecha(new \DateTime());

        // Validar si el campo cliente está presente
        if (!isset($json_pedido['cliente'])) {
            return new JsonResponse(['error' => 'Cliente ID is missing in the request'], 400);
        }

        // Encontrar el cliente por su ID
        $cliente = $entityManager->getRepository(Cliente::class)->find($json_pedido['cliente']);
        if (!$cliente) {
            return new JsonResponse(['error' => 'Cliente not found'], 404);
        }

        // Crear un nuevo pedido
        $pedido->setCliente($cliente);
        $pedido->setTotal($json_pedido['total']);
        $pedido->setEstado("procesado");
        $pedido->setDireccionEntrega($json_pedido['direccion_entrega']);

        // Crear nuevas lineas de pedido
        foreach ($json_pedido['lineaPedidos'] as $lineaData) {

            // Validar si el campo libro está presente
            $libroId = is_array($lineaData['libro']) ? ($lineaData['libro']['id'] ?? null) : $lineaData['libro'];

            // Validar si el campo libro está presente
            if (!$libroId) {
                return new JsonResponse(['error' => 'Libro ID is missing in a lineaPedido'], 400);
            }

            // Encontrar el libro por su ID
            $libro = $entityManager->getRepository(Libro::class)->find($libroId);
            if (!$libro) {
                return new JsonResponse(['error' => 'Libro not found'], 404);
            }

            // Crear una nueva linea de pedido
            $lineaPedido = new LineaPedido();
            $lineaPedido->setCantidad($lineaData['cantidad']);
            $lineaPedido->setPrecioUnitario($lineaData['precio_unitario']);
            $lineaPedido->setLibro($libro);
            $lineaPedido->setIdPedido($pedido);

            // Persistir la linea de pedido
            $entityManager->persist($lineaPedido);
        }

        // Persistir el pedido
        $entityManager->persist($pedido);
        $entityManager->flush();

        // Retornar una respuesta JSON
        return $this->json(['mensaje' => 'Pedido guardado correctamente']);


    }

    /**
     *
     * Obtiene todos los pedidos de un cliente.
     *
     * @param int $id
     * @param PedidoRepository $pedidoRepository
     * @param SerializerInterface $serializer
     * @return Response
     */

    #[Route('/cliente/{id}', name: 'pedidos_by_cliente', methods: ['GET'])]
    public function getPedidosByCliente(
        int $id,
        PedidoRepository $pedidoRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        // Encontrar los pedidos por el ID del cliente
        $pedidos = $pedidoRepository->findBy(['cliente' => $id]);

        // Validar si no se encontraron pedidos
        if (!$pedidos) {
            return new JsonResponse(['message' => 'No orders found for this client'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Serializar los pedidos con un grupo definido
        $jsonPedidos = $serializer->serialize($pedidos, 'json', [
            'groups' => ['pedido:read']
        ]);

        return new JsonResponse($jsonPedidos, JsonResponse::HTTP_OK, [], true);
    }

    /**
     *
     * Obtiene las estadísticas de los pedidos de un cliente.
     *
     * @param int $id
     * @param PedidoRepository $pedidoRepository
     * @return JsonResponse
     */

    #[Route('/cliente/{id}/estadisticas', name: 'estadisticas_pedidos_cliente', methods: ['GET'])]
    public function getPedidoStatsByCliente(int $id, PedidoRepository $pedidoRepository)
    {

        // Contar los pedidos totales, entregados y procesados
        $totales =count($pedidoRepository->findBy(['cliente' => $id]));
        $entregados =count($pedidoRepository->findBy([
            'cliente' => $id,
            'estado' => 'entregado'
        ]));
        $procesados =count($pedidoRepository->findBy([
            'cliente' => $id,
            'estado' => 'procesado'
        ]));

        return $this->json([
            'totales' => $totales,
            'entregados' => $entregados,
            'procesados' => $procesados
        ]);

    }

}
