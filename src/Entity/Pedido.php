<?php

namespace App\Entity;

use App\Repository\PedidoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PedidoRepository::class)]
#[ORM\Table(name: "pedido", schema: "puntosafa")]

class Pedido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pedido:read'])]
    private ?int $id = null;

    #[ORM\Column(name: "fecha", type: Types::DATETIME_MUTABLE)]
    #[Groups(['pedido:read'])]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column(name: "total", type: Types::FLOAT)]
    #[Groups(['pedido:read'])]
    private ?float $total = null;

    #[ORM\Column(name: "estado", type: Types::STRING, length: 100)]
    #[Groups(['pedido:read'])]
    private ?string $estado = null;

    #[ORM\Column(name: "direccion_entrega", type: Types::STRING, length: 200)]
    #[Groups(['pedido:read'])]
    private ?string $direccion_entrega = null;


    #[ORM\OneToOne(targetEntity: Cliente::class,  cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: "id_cliente", referencedColumnName: "id", nullable: false)]
    #[Groups(['pedido:read'])]
    private ?Cliente $cliente=null;

    /**
     * @var Collection<int, LineaPedido>
     */
    #[ORM\OneToMany(targetEntity: LineaPedido::class, mappedBy: 'pedido', cascade: ['persist', 'remove'])]
    #[Groups(['pedido:read'])]
    private Collection $lineaPedidos;

    public function getCliente(): ?Cliente
    {
        return $this->cliente;
    }

    public function setCliente(?Cliente $cliente): static
    {
        $this->cliente = $cliente;
        return $this;
    }

    public function __construct()
    {
        $this->lineaPedidos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getDireccionEntrega(): ?string
    {
        return $this->direccion_entrega;
    }

    public function setDireccionEntrega(string $direccion_entrega): static
    {
        $this->direccion_entrega = $direccion_entrega;

        return $this;
    }

    /**
     * @return Collection<int, LineaPedido>
     */
    public function getLineaPedidos(): Collection
    {
        return $this->lineaPedidos;
    }

    public function addLineaPedido(LineaPedido $lineaPedido): static
    {
        if (!$this->lineaPedidos->contains($lineaPedido)) {
            $this->lineaPedidos->add($lineaPedido);
            $lineaPedido->setIdPedido($this);
        }

        return $this;
    }

    public function removeLineaPedido(LineaPedido $lineaPedido): static
    {
        if ($this->lineaPedidos->removeElement($lineaPedido)) {
            // set the owning side to null (unless already changed)
            if ($lineaPedido->getIdPedido() === $this) {
                $lineaPedido->setIdPedido(null);
            }
        }

        return $this;
    }


}
