<?php

namespace App\Controller;

use App\Entity\Cliente;
use App\Entity\Pedido;
use App\Repository\PedidoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
        $fecha = new \DateTime($json_pedido['fecha']);
        $pedido->setFecha($fecha);
        $clienteId = $json_pedido['cliente']; // Assuming 'cliente' contains the ID of the existing client
        $cliente = $entityManager->getRepository(Cliente::class)->find($clienteId);
        $pedido->setCliente($cliente);
        $pedido->setTotal($json_pedido['total']);
        $pedido->setEstado($json_pedido['estado']);
        $pedido->SetDireccionEntrega($json_pedido['direccion']);
        $entityManager->persist($pedido);
        $entityManager->flush();

        return $this->json(['mensaje' => 'Datos guardados correctamente']);




    }

}
