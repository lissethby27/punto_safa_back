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

    #[Route('/all', name: 'get_all_pedidos', methods: ['GET'])]
    public function getAll(PedidoRepository $pedidoRepository, SerializerInterface $serializer): Response
    {
        $pedidos = $pedidoRepository->findAll();

        // Serialize the pedidos with a defined group
        $json = $serializer->serialize($pedidos, 'json', ['groups' => 'pedido:read']);

        return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/save', name: 'save_pedidos', methods: ['POST'])]
    public function savePedido(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $json_pedido = json_decode($request->getContent(), true);

        $pedido = new Pedido();
        $pedido->setFecha(new \DateTime());

        if (!isset($json_pedido['cliente'])) {
            return new JsonResponse(['error' => 'Cliente ID is missing in the request'], 400);
        }

        $cliente = $entityManager->getRepository(Cliente::class)->find($json_pedido['cliente']);
        if (!$cliente) {
            return new JsonResponse(['error' => 'Cliente not found'], 404);
        }

        $pedido->setCliente($cliente);
        $pedido->setTotal($json_pedido['total']);
        $pedido->setEstado("procesado");
        $pedido->setDireccionEntrega($json_pedido['direccion_entrega']);

        foreach ($json_pedido['lineaPedidos'] as $lineaData) {
            // Handle both formats: libro as an ID or as an object
            $libroId = is_array($lineaData['libro']) ? ($lineaData['libro']['id'] ?? null) : $lineaData['libro'];

            if (!$libroId) {
                return new JsonResponse(['error' => 'Libro ID is missing in a lineaPedido'], 400);
            }

            $libro = $entityManager->getRepository(Libro::class)->find($libroId);
            if (!$libro) {
                return new JsonResponse(['error' => 'Libro not found'], 404);
            }

            $lineaPedido = new LineaPedido();
            $lineaPedido->setCantidad($lineaData['cantidad']);
            $lineaPedido->setPrecioUnitario($lineaData['precio_unitario']);
            $lineaPedido->setLibro($libro);
            $lineaPedido->setIdPedido($pedido);

            $entityManager->persist($lineaPedido);
        }

        $entityManager->persist($pedido);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Pedido guardado correctamente']);


    }

    #[Route('/cliente/{id}', name: 'pedidos_by_cliente', methods: ['GET'])]
    public function getPedidosByCliente(
        int $id,
        PedidoRepository $pedidoRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        // Fetch pedidos for the given cliente ID
        $pedidos = $pedidoRepository->findBy(['cliente' => $id]);

        if (!$pedidos) {
            return new JsonResponse(['message' => 'No orders found for this client'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Serialize pedidos along with related data (Cliente, LineaPedido, Libro)
        $jsonPedidos = $serializer->serialize($pedidos, 'json', [
            'groups' => ['pedido:read']
        ]);

        return new JsonResponse($jsonPedidos, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/cliente/{id}/estadisticas', name: 'estadisticas_pedidos_cliente', methods: ['GET'])]
    public function getPedidoStatsByCliente(int $id, PedidoRepository $pedidoRepository)
    {

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
