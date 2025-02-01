<?php

namespace App\Controller;

use App\Repository\LineaPedidoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/linea_pedido', name: 'app_linea_pedido')]
final class LineaPedidoController extends AbstractController
{

    #[Route('/all', name: 'get_all_lineaPedidos', methods: ['GET'])]
    public function getAll(LineaPedidoRepository $lineaPedidoRepository, SerializerInterface $serializer): Response
    {
        $lineaPedidos = $lineaPedidoRepository->findAll();

        // Serialize the pedidos with a defined group
        $json = $serializer->serialize($lineaPedidos, 'json', ['groups' => 'pedido:read']);

        return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}
