<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\Table(name: "usuario", schema: "puntosafa")]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: "nick", type: Types::STRING,length: 100)]
    private ?string $nick = null;

    #[ORM\Column(name: "contrasena", type: Types::STRING ,length: 255)]
    private ?string $contrasena = null;

    #[ORM\Column(name: "rol", type: Types::STRING, length: 100)]
    private ?string $rol = null;

    #[ORM\Column(name: "email", type: Types::STRING, length: 150)]
    private ?string $email = null;

    /**
     * @var Collection<int, Resena>
     */
     #[ORM\OneToMany(targetEntity: Resena::class, mappedBy: 'usuario')]
     private Collection $usuario;


    public function __construct()
    {
        $this->usuario = new ArrayCollection();
    }

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

    public function getRoles(): array
    {
        return [$this->rol ? 'ROLE_' . strtoupper($this->rol) : 'ROLE_USER'];
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

    /**
     * @return Collection<int, Resena>
     */
    public function getUsuario(): Collection
    {
        return $this->usuario;
    }

    public function addUsuario(Resena $usuario): static
    {
        if (!$this->usuario->contains($usuario)) {
            $this->usuario->add($usuario);
            $usuario->setUsuario($this);
        }

        return $this;
    }

    public function removeUsuario(Resena $usuario): static
    {
        if ($this->usuario->removeElement($usuario)) {
            // set the owning side to null (unless already changed)
            if ($usuario->getUsuario() === $this) {
                $usuario->setUsuario(null);
            }
        }

        return $this;
    }



    public function eraseCredentials(): void
    {
        // Si tienes datos sensibles temporales, bórralos aquí
    }

    public function getUserIdentifier(): string
    {
        return $this->nick;
    }

    public function getPassword(): ?string
    {
        return $this->contrasena;
    }





}