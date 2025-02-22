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
//        $fecha = new \DateTime($json_pedido['fecha']);
//        $pedido->setFecha($fecha);
        $pedido->setFecha(new \DateTime());
        $clienteId = $json_pedido['cliente']; // Assuming 'cliente' contains the ID of the existing client
        $cliente = $entityManager->getRepository(Cliente::class)->find($clienteId);
        $pedido->setCliente($cliente);
        $pedido->setTotal($json_pedido['total']);
        $pedido->setEstado("procesado");

        $data = json_decode($request->getContent(), true);
// Check if 'direccion_entrega' is set in the request
        if (!isset($data['direccion_entrega'])) {
            return new JsonResponse(['message' => 'Direccion not found'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $pedido->setDireccionEntrega($data['direccion_entrega']);
        $codigo = 'PO' . (new \DateTime())->format('dmY');
        $pedido->setCodigo($codigo);

        $cliente = $entityManager->getRepository(Cliente::class)->find($json_pedido['cliente']);
        if (!isset($json_pedido['cliente'])) {
            return new JsonResponse(['error' => 'Cliente ID is missing in the request'], 400);
        }

        if (!$cliente) {
            return new JsonResponse(['error' => 'Cliente not found'], 404);
        }
        $pedido->setCliente($cliente);

        foreach ($json_pedido['lineaPedidos'] as $lineaData) {
            $libro = $entityManager->getRepository(Libro::class)->find($lineaData['libro']);
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

        return $this->json(['mensaje' => 'Datos guardados correctamente']);


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
