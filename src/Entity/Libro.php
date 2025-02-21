<?php

namespace App\Entity;

use App\Repository\LibroRepository;
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
    #[Groups(['pedido:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'titulo', type: Types::STRING, length: 255)]
    #[Groups(['pedido:read'])]
    private ?string $titulo = null;

    #[ORM\Column(name: 'resumen', type: Types::STRING, length: 800, nullable: true)]
    #[Groups(['pedido:read'])]
    private ?string $resumen = null;

    #[ORM\Column(name: 'anio_publicacion', type: Types::DATE_MUTABLE)]
    #[Groups(['pedido:read'])]
    private ?\DateTimeInterface $anioPublicacion = null;

    #[ORM\Column(name: 'precio', type: Types::FLOAT)]
    #[Groups(['pedido:read'])]
    private ?float $precio = null;

    #[ORM\Column(name: 'ISBN', type: Types::STRING, length: 20)]
    #[Groups(['pedido:read'])]
    private ?string $ISBN = null;

    #[ORM\Column(name: 'editorial', type: Types::STRING, length: 200)]
    #[Groups(['pedido:read'])]
    private ?string $editorial = null;

    #[ORM\Column(name: 'imagen', type: Types::STRING, length: 500)]
    #[Groups(['pedido:read'])]
    private ?string $imagen = null;

    #[ORM\Column(name: 'idioma', type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['pedido:read'])]
    private ?string $idioma = null;

    #[ORM\Column(name: 'num_paginas', type: Types::INTEGER, nullable: true)]
    #[Groups(['pedido:read'])]
    private ?int $numPaginas = null;

    #[ORM\ManyToOne(targetEntity: Autor::class, inversedBy: "libros")]
    #[ORM\JoinColumn(name: 'id_autor', nullable: false)]
    #[Groups(['pedido:read'])]
    private ?Autor $autor = null;

    #[ORM\ManyToOne(targetEntity: Categoria::class, inversedBy: "libros")]
    #[ORM\JoinColumn(name: "id_categoria", referencedColumnName: "id", nullable: false)]
    #[Groups(['pedido:read'])]
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


}
