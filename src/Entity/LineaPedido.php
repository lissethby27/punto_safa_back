<?php

namespace App\Entity;

use App\Repository\LineaPedidoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LineaPedidoRepository::class)]
#[ORM\Table(name: "linea_pedido", schema: "puntosafa")]
class LineaPedido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column (name:'id',type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: 'cantidad', type: "integer")]
    private ?int $cantidad = null;

    #[ORM\Column(name: 'precio_unitario', type: "float")]
    private ?float $precio_unitario = null;

    #[ORM\ManyToOne(inversedBy: 'lineaPedidos')]
    #[ORM\JoinColumn(name: "id_libro", nullable: false)]
    private ?Libro $libro = null;

    #[ORM\ManyToOne(inversedBy: 'lineaPedidos')]
    #[ORM\JoinColumn(name: "id_pedido", referencedColumnName: "id", nullable: false)]
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

    public function getLibro(): ?Libro
    {
        return $this->libro;
    }

    public function setLibro(?Libro $libro): static
    {
        $this->libro = $libro;

        return $this;
    }

    public function getIdPedido(): ?Pedido
    {
        return $this->pedido;
    }

    public function setIdPedido(?Pedido $pedido): static
    {
        $this->pedido = $pedido;

        return $this;
    }
}
