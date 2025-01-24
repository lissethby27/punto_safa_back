<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\Table(name: "usuario", schema: "puntosafa")]

class Usuario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nick = null;

    #[ORM\Column(length: 100)]
    private ?string $contrasena = null;

    #[ORM\Column(length: 100)]
    private ?string $rol = null;

    #[ORM\Column(length: 150)]
    private ?string $email = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNick(): ?string
    {
        return $this->nick;
    }

    public function setNick(string $nick): static
    {
        $this->nick = $nick;

        return $this;
    }

    public function getContrasena(): ?string
    {
        return $this->contrasena;
    }

    public function setContrasena(string $contrasena): static
    {
        $this->contrasena = $contrasena;

        return $this;
    }

    public function getRol(): ?string
    {
        return $this->rol;
    }

    public function setRol(string $rol): static
    {
        $this->rol = $rol;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }
}
