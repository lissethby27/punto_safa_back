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
    #[Groups(['libro_list', 'libro_detail'])]
    private ?int $id = null;

    #[ORM\Column(name: 'titulo', type: Types::STRING, length: 255)]
    #[Groups(['libro_list', 'libro_detail'])]
    private ?string $titulo = null;

    #[ORM\Column(name: 'resumen', type: Types::STRING, length: 800, nullable: true)]
    #[Groups(['libro_list', 'libro_detail'])]
    private ?string $resumen = null;

    #[ORM\Column(name: 'anio_publicacion', type: Types::DATE_MUTABLE)]
    #[Groups(['libro_list', 'libro_detail'])]
    private ?\DateTimeInterface $anioPublicacion = null;

    #[ORM\Column(name: 'precio', type: Types::FLOAT)]
    #[Groups(['libro_list', 'libro_detail'])]
    private ?float $precio = null;

    #[ORM\Column(name: 'ISBN', type: Types::STRING, length: 20)]
    #[Groups(['libro_list', 'libro_detail'])]
    private ?string $ISBN = null;

    #[ORM\Column(name: 'editorial', type: Types::STRING, length: 200)]
    #[Groups(['libro_list', 'libro_detail'])]
    private ?string $editorial = null;

    #[ORM\Column(name: 'imagen', type: Types::STRING, length: 500)]
    #[Groups(['libro_list','libro_detail'])]
    private ?string $imagen = null;

    #[ORM\Column(name: 'idioma', type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['libro_list','libro_detail'])]
    private ?string $idioma = null;

    #[ORM\Column(name: 'num_paginas', type: Types::INTEGER, nullable: true)]
    #[Groups(['libro_list','libro_detail'])]
    private ?int $numPaginas = null;

    #[ORM\ManyToOne(targetEntity: Autor::class, inversedBy: "libros")]
    #[ORM\JoinColumn(name: 'id_autor', nullable: false)]
    #[Groups(['libro_list'])]
    private ?Autor $autor = null;

    #[ORM\ManyToOne(targetEntity: Categoria::class, inversedBy: "libros")]
    #[ORM\JoinColumn(name: "id_categoria", referencedColumnName: "id", nullable: false)]
    #[Groups(['libro_list'])]
    private ?Categoria $categoria = null;

    /**
     * @var Collection<int, LineaPedido>
     */
    #[ORM\OneToMany(targetEntity: LineaPedido::class, mappedBy: 'libro')]
    private Collection $lineaPedidos;

    public function __construct()
    {
        $this->lineaPedidos = new ArrayCollection();
    }
    #[Groups(['libro_list'])]
    public function getId(): ?int
    {
        return $this->id;
    }
    #[Groups(['libro_list'])]
    public function getTitulo(): ?string
    {
        return $this->titulo;
    }
    #[Groups(['libro_list'])]
    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getResumen(): ?string
    {
        return $this->resumen;
    }
    #[Groups(['libro_list'])]
    public function setResumen(?string $resumen): static
    {
        $this->resumen = $resumen;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getAnioPublicacion(): ?\DateTimeInterface
    {
        return $this->anioPublicacion;
    }
    #[Groups(['libro_list'])]
    public function setAnioPublicacion(\DateTimeInterface $anioPublicacion): static
    {
        $this->anioPublicacion = $anioPublicacion;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getPrecio(): ?float
    {
        return $this->precio;
    }
    #[Groups(['libro_list'])]
    public function setPrecio(float $precio): static
    {
        $this->precio = $precio;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getISBN(): ?string
    {
        return $this->ISBN;
    }
    #[Groups(['libro_list'])]
    public function setISBN(string $ISBN): static
    {
        $this->ISBN = $ISBN;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getEditorial(): ?string
    {
        return $this->editorial;
    }
    #[Groups(['libro_list'])]
    public function setEditorial(string $editorial): static
    {
        $this->editorial = $editorial;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getImagen(): ?string
    {
        return $this->imagen;
    }
    #[Groups(['libro_list'])]
    public function setImagen(string $imagen): static
    {
        $this->imagen = $imagen;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getIdioma(): ?string
    {
        return $this->idioma;
    }
    #[Groups(['libro_list'])]
    public function setIdioma(?string $idioma): static
    {
        $this->idioma = $idioma;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getNumPaginas(): ?int
    {
        return $this->numPaginas;
    }
    #[Groups(['libro_list'])]
    public function setNumPaginas(?int $numPaginas): static
    {
        $this->numPaginas = $numPaginas;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getAutor(): ?Autor
    {
        return $this->autor;
    }

    public function setAutor(?Autor $autor): static
    {
        $this->autor = $autor;
        return $this;
    }
    #[Groups(['libro_list'])]
    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }
    #[Groups(['libro_list'])]
    public function setCategoria(?Categoria $categoria): static
    {
        $this->categoria = $categoria;
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
            $lineaPedido->setLibro($this);
        }
        return $this;
    }

    public function removeLineaPedido(LineaPedido $lineaPedido): static
    {
        if ($this->lineaPedidos->removeElement($lineaPedido)) {
            if ($lineaPedido->getLibro() === $this) {
                $lineaPedido->setLibro(null);
            }
        }
        return $this;
    }
}
