<?php

namespace App\Entity;

use App\Repository\LibroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LibroRepository::class)]
#[ORM\Table(name: "libro", schema: "puntosafa")]
class Libro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id',type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: 'titulo',length: 255)]
    private ?string $titulo = null;

    #[ORM\Column(name:'resumen',length: 800, nullable: true)]
    private ?string $resumen = null;

    #[ORM\Column(name:'anio_publicacion',type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $anio_publicacion = null;

    #[ORM\Column(name:'precio',type: Types::FLOAT)]
    private ?float $precio = null;

    #[ORM\Column(name:'ISBN',length: 20)]
    private ?string $ISBN = null;

    #[ORM\Column(name:'editorial',length: 200)]
    private ?string $editorial = null;

    #[ORM\Column(name:'image',length: 500)]
    private ?string $imagen = null;

    #[ORM\Column(name:'idioma',length: 100, nullable: true)]
    private ?string $idioma = null;

    #[ORM\Column(name:'num_paginas',type: Types::INTEGER, nullable: true)]
    private ?int $num_paginas = null;

    #[ORM\ManyToOne(inversedBy: 'libros')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Autor $autor = null;



    #[ORM\ManyToOne(inversedBy: 'categoria')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categoria $categoria = null;

    /**
     * @var Collection<int, Resena>
     */
    // #[ORM\OneToMany(targetEntity: Resena::class, mappedBy: 'libro')]
    // private Collection $resenas;

    /**
     * @var Collection<int, LineaPedido>
     */
    #[ORM\OneToMany(targetEntity: LineaPedido::class, mappedBy: 'libro')]
    private Collection $lineaPedidos;

    public function __construct()
    {
        $this->libro = new ArrayCollection();
        $this->lineaPedidos = new ArrayCollection();
    }

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
        return $this->anio_publicacion;
    }

    public function setAnioPublicacion(\DateTimeInterface $anio_publicacion): static
    {
        $this->anio_publicacion = $anio_publicacion;

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
        return $this->num_paginas;
    }

    public function setNumPaginas(int $num_paginas): static
    {
        $this->num_paginas = $num_paginas;

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
     * @return Collection<int, Resena>
     */
    public function getLibro(): Collection
    {
        return $this->libro;
    }

    public function addLibro(Resena $libro): static
    {
        if (!$this->libro->contains($libro)) {
            $this->libro->add($libro);
            $libro->setIdLibro($this);
        }

        return $this;
    }

    public function removeLibro(Resena $libro): static
    {
        if ($this->libro->removeElement($libro)) {
            // set the owning side to null (unless already changed)
            if ($libro->getIdLibro() === $this) {
                $libro->setIdLibro(null);
            }
        }

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
            $lineaPedido->setIdLibro($this);
        }

        return $this;
    }

    public function removeLineaPedido(LineaPedido $lineaPedido): static
    {
        if ($this->lineaPedidos->removeElement($lineaPedido)) {
            // set the owning side to null (unless already changed)
            if ($lineaPedido->getIdLibro() === $this) {
                $lineaPedido->setIdLibro(null);
            }
        }

        return $this;
    }
}
