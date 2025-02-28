<?php

namespace App\Entity;

use App\Repository\ClienteRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: ClienteRepository::class)]
#[ORM\Table(name: "cliente", schema: "puntosafa")]
class Cliente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pedido:read'])]
    private ?int $id = null;

    #[ORM\Column(name: "nombre", type: Types::STRING, length: 100)]
    #[Groups(['pedido:read'])]
    private ?string $nombre = null;
    #[ORM\Column(name: "apellidos", type: Types::STRING, length: 100)]
    #[Groups(['pedido:read'])]
    private ?string $apellidos = null;

    #[ORM\Column(name: "DNI", type: Types::STRING, length: 100, unique: true)]
    #[Groups(['pedido:read'])]
    private ?string $DNI = null;

    #[ORM\Column(name: "foto", type: Types::STRING, length: 255)]
    #[Groups(['pedido:read'])]
    private ?string $foto = null;

    #[ORM\Column(name: "direccion", type: Types::STRING, length: 200)]
    #[Groups(['pedido:read'])]
    private ?string $direccion = null;

    #[ORM\Column(name: "telefono", type: Types::STRING, length: 100)]
    #[Groups(['pedido:read'])]
    private ?string $telefono = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class, fetch: "EAGER")]
    #[ORM\JoinColumn(name: "id_usuario", referencedColumnName: "id", nullable: false)]
    #[Groups(['pedido:read'])]
    private ?Usuario $usuario = null;


     
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
}