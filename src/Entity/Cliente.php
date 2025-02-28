<?php


namespace App\Entity;

use App\Repository\ClienteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: ClienteRepository::class)]
#[ORM\Table(name: "cliente", schema: "puntosafa")]
class Cliente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: "nombre", type: Types::STRING, length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(name: "apellidos", type: Types::STRING, length: 100)]
    private ?string $apellidos = null;

    #[ORM\Column(name: "DNI", type: Types::STRING, length: 100, unique: true)]
    private ?string $DNI = null;

    #[ORM\Column(name: "foto", type: Types::STRING, length: 255)]
    private ?string $foto = null;

    #[ORM\Column(name: "direccion", type: Types::STRING, length: 200)]
    private ?string $direccion = null;

    #[ORM\Column(name: "telefono", type: Types::STRING, length: 100)]
    private ?string $telefono = null;

    #[ORM\OneToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'id_usuario', referencedColumnName: 'id', nullable: false)]
    private ?Usuario $usuario = null;

    #[ORM\OneToMany(targetEntity: Pedido::class, mappedBy: 'cliente')]
    #[Groups(['cliente:read'])] // Asegúrate de que este grupo esté definido
    private Collection $pedidos;

    public function __construct()
    {
        $this->pedidos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): static
    {
        $this->apellidos = $apellidos;
        return $this;
    }

    public function getDNI(): ?string
    {
        return $this->DNI;
    }

    public function setDNI(string $DNI): static
    {
        $this->DNI = $DNI;
        return $this;
    }

    public function getFoto(): ?string
    {
        return $this->foto;
    }

    public function setFoto(string $foto): static
    {
        $this->foto = $foto;
        return $this;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(string $direccion): static
    {
        $this->direccion = $direccion;
        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): static
    {
        $this->telefono = $telefono;
        return $this;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(Usuario $usuario): static
    {
        $this->usuario = $usuario;
        return $this;
    }

    /**
     * @return Collection<int, Pedido>
     */
    public function getPedidos(): Collection
    {
        return $this->pedidos;
    }

    public function addPedido(Pedido $pedido): self
    {
        if (!$this->pedidos->contains($pedido)) {
            $this->pedidos[] = $pedido;
            $pedido->setCliente($this);
        }
        return $this;
    }

    public function removePedido(Pedido $pedido): self
    {
        if ($this->pedidos->removeElement($pedido)) {
            if ($pedido->getCliente() === $this) {
                $pedido->setCliente(null);
            }
        }
        return $this;
    }
}