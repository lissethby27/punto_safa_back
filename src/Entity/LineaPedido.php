<?php

namespace App\Entity;

use App\Repository\LineaPedidoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LineaPedidoRepository::class)]
#[ORM\Table(name: "linea_pedido", schema: "puntosafa")]
class LineaPedido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column (name:'id',type: "integer")]
    #[Groups(['linea_pedido:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'cantidad', type: "integer")]
    #[Groups(['linea_pedido:read'])]
    private ?int $cantidad = null;

    #[ORM\Column(name: 'precio_unitario', type: "float")]
    #[Groups(['linea_pedido:read'])]
    private ?float $precio_unitario = null;

    #[ORM\ManyToOne(inversedBy: 'lineaPedidos')]
    #[ORM\JoinColumn(name: "id_libro", nullable: false)]
    #[Groups(['linea_pedido:read'])]
    private ?Libro $libro = null;


    #[ORM\ManyToOne(inversedBy: 'lineaPedidos')]
    #[ORM\JoinColumn(name: "id_libro", referencedColumnName: "id", nullable: false)]
    #[Groups(['linea_pedido:read'])]
    private ?Pedido $pedido = null;


    public function getId(): ?int
    {
        return $this->id;

    }

    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function setCantidad(int $cantidad): static
    {
        $this->cantidad = $cantidad;

        return $this;
    }

    public function getPrecioUnitario(): ?float
    {
        return $this->precio_unitario;
    }

    public function setPrecioUnitario(float $precio_unitario): static
    {
        $this->precio_unitario = $precio_unitario;

        return $this;
    }

    public function getIdLibro(): ?int
    {
        return $this->id_libro;
    }

    public function setIdLibro(?int $id_libro): void
    {
        $this->id_libro = $id_libro;
    }

    public function getIdPedido(): ?int
    {
        return $this->id_pedido;
    }

    public function setIdPedido(?int $id_pedido): void
    {
        $this->id_pedido = $id_pedido;
    }

//    public function getLibro(): ?Libro
//    {
//        return $this->libro;
//    }
//
//    public function setLibro(?Libro $libro): void
//    {
//        $this->libro = $libro;
//    }

//    public function getPedido(): ?Pedido
//    {
//        return $this->pedido;
//    }
//
//    public function setPedido(?Pedido $pedido): void
//    {
//        $this->pedido = $pedido;
//    }





}
