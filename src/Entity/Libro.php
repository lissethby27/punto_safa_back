<?php

namespace App\Entity;

use App\Repository\LibroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LibroRepository::class)]
#[ORM\Table(name: "libro", schema: "puntosafa")]
class Libro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'titulo', type: Types::STRING, length: 255)]
    private ?string $titulo = null;

    #[ORM\Column(name: 'resumen', type: Types::STRING, length: 800, nullable: true)]
    private ?string $resumen = null;

    #[ORM\Column(name: 'anio_publicacion', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $anioPublicacion = null;

    #[ORM\Column(name: 'precio', type: Types::FLOAT)]
    private ?float $precio = null;

    #[ORM\Column(name: 'ISBN', type: Types::STRING, length: 20)]
    private ?string $ISBN = null;

    #[ORM\Column(name: 'editorial', type: Types::STRING, length: 200)]
    private ?string $editorial = null;

    #[ORM\Column(name: 'imagen', type: Types::STRING, length: 500)]
    private ?string $imagen = null;

    #[ORM\Column(name: 'idioma', type: Types::STRING, length: 100, nullable: true)]
    private ?string $idioma = null;

    #[ORM\Column(name: 'num_paginas', type: Types::INTEGER, nullable: true)]
    private ?int $numPaginas = null;

    #[ORM\ManyToOne(targetEntity: Autor::class, inversedBy: "libros")]
    #[ORM\JoinColumn(name: 'id_autor', nullable: false)]
    private ?Autor $autor = null;

    #[ORM\ManyToOne(targetEntity: Categoria::class, inversedBy: "libros")]
    #[ORM\JoinColumn(name: "id_categoria", referencedColumnName: "id", nullable: false)]
    private ?Categoria $categoria = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;
        return $this;
    }

    public function getResumen(): ?string
    {
        return $this->resumen;
    }

    public function setResumen(?string $resumen): static
    {
        $this->resumen = $resumen;
        return $this;
    }

    public function getAnioPublicacion(): ?\DateTimeInterface
    {
        return $this->anioPublicacion;
    }

    public function setAnioPublicacion(\DateTimeInterface $anioPublicacion): static
    {
        $this->anioPublicacion = $anioPublicacion;
        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): static
    {
        $this->precio = $precio;
        return $this;
    }

    public function getISBN(): ?string
    {
        return $this->ISBN;
    }

    public function setISBN(string $ISBN): static
    {
        $this->ISBN = $ISBN;
        return $this;
    }

    public function getEditorial(): ?string
    {
        return $this->editorial;
    }

    public function setEditorial(string $editorial): static
    {
        $this->editorial = $editorial;
        return $this;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(string $imagen): static
    {
        $this->imagen = $imagen;
        return $this;
    }

    public function getIdioma(): ?string
    {
        return $this->idioma;
    }

    public function setIdioma(?string $idioma): static
    {
        $this->idioma = $idioma;
        return $this;
    }

    public function getNumPaginas(): ?int
    {
        return $this->numPaginas;
    }

    public function setNumPaginas(?int $numPaginas): static
    {
        $this->numPaginas = $numPaginas;
        return $this;
    }

    public function getAutor(): ?Autor
    {
        return $this->autor;
    }

    public function setAutor(?Autor $autor): static
    {
        $this->autor = $autor;
        return $this;
    }

    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(?Categoria $categoria): static
    {
        $this->categoria = $categoria;
        return $this;
    }

    /**
     * @return Collection<int, LineaPedido>
     */
//    public function getLineaPedidos(): Collection
//    {
//        return $this->lineaPedidos;
//    }

//    public function addLineaPedido(LineaPedido $lineaPedido): static
//    {
//        if (!$this->lineaPedidos->contains($lineaPedido)) {
//            $this->lineaPedidos->add($lineaPedido);
//            $lineaPedido->setLibro($this);
//        }
//        return $this;
//    }
//
//    public function removeLineaPedido(LineaPedido $lineaPedido): static
//    {
//        if ($this->lineaPedidos->removeElement($lineaPedido)) {
//            if ($lineaPedido->getLibro() === $this) {
//                $lineaPedido->setLibro(null);
//            }
//        }
//        return $this;
//    }
}
