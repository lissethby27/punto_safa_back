<?php

namespace App\Entity;

use App\Repository\ResenaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResenaRepository::class)]
#[ORM\Table(name: "resena", schema: "puntosafa")]
class Resena
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name:'id',type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name:'calificacion',type: "integer")]
    private ?int $calificacion = null;

    #[ORM\Column(name:'comentario',length: 200)]
    private ?string $comentario = null;

    #[ORM\Column(name:'fecha',type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\ManyToOne(targetEntity: Libro::class)]
    #[ORM\JoinColumn(name: "id_libro", referencedColumnName: "id", nullable: false)]
    private ?Libro $libro = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: "id_usuario", referencedColumnName: "id", nullable: false)]
    private ?Usuario $usuario = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCalificacion(): ?int
    {
        return $this->calificacion;
    }

    public function setCalificacion(int $calificacion): static
    {
        $this->calificacion = $calificacion;

        return $this;
    }

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function setComentario(string $comentario): static
    {
        $this->comentario = $comentario;

        return $this;
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

    public function getLibro(): ?Libro
    {
        return $this->libro;
    }

    public function setLibro(?Libro $libro): static
    {
        $this->libro = $libro;

        return $this;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }
}
