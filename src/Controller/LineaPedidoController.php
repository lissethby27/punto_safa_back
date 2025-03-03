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

    /**
     *
     * Obtiene todas las lineas de pedido
     *
     * @param LineaPedidoRepository $lineaPedidoRepository
     * @param SerializerInterface $serializer
     * @return Response
     */
    #[Route('/all', name: 'get_all_lineaPedidos', methods: ['GET'])]
    public function getAll(LineaPedidoRepository $lineaPedidoRepository, SerializerInterface $serializer): Response
    {
        // Obtenemos todas las lineas de pedido
        $lineaPedidos = $lineaPedidoRepository->findAll();

        // Serializamos el resultado
        $jsonContent = $serializer->serialize($lineaPedidos, 'json');

        return new Response($jsonContent, Response::HTTP_OK, [], true);
    }
}
