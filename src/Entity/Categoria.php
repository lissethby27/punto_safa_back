<?php

namespace App\Entity;

use App\Repository\CategoriaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategoriaRepository::class)]
#[ORM\Table(name: "categoria", schema: "puntosafa")]
class Categoria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: 'integer')]
    #[Groups(['categoria_detail', 'libro_list'])]
    private ?int $id = null;

    #[ORM\Column(name: 'nombre', length: 100)]
    #[Groups(['categoria_detail', 'libro_list'])]
    private ?string $nombre = null;

    #[ORM\Column(name: 'descripcion', length: 800)]
    #[Groups(['categoria_detail', 'libro_list'])]
    private ?string $descripcion = null;

    /**
     * @var Collection<int, Libro>
     */
//    #[ORM\OneToMany(targetEntity: Libro::class, mappedBy: 'categoria')]
//    private Collection $libros;  // Cambié el nombre de "categoria" a "libros"

    public function __construct()
    {
        $this->libros = new ArrayCollection();
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

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    /**
     * @return Collection<int, Libro>
     */
//    public function getLibros(): Collection  // Cambié getCategoria() a getLibros()
//    {
//        return $this->libros;
//    }

//    public function addLibro(Libro $libro): static  // Renombré addCategorium() a addLibro()
//    {
//        if (!$this->libros->contains($libro)) {
//            $this->libros->add($libro);
//            $libro->setCategoria($this);
//        }
//        return $this;
//    }

//    public function removeLibro(Libro $libro): static  // Renombré removeCategorium() a removeLibro()
//    {
//        if ($this->libros->removeElement($libro)) {
//            if ($libro->getCategoria() === $this) {
//                $libro->setCategoria(null);
//            }
//        }
//        return $this;
//    }
}
