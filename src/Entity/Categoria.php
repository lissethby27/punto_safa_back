<?php

namespace App\Entity;

use App\Repository\CategoriaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriaRepository::class)]
#[ORM\Table(name: "categoria", schema: "puntosafa")]
class Categoria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id",type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name:'nombre',length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(name:'descripcion',length: 800)]
    private ?string $descripcion = null;



    /**
     * @var Collection<int, Libro>
     */
    #[ORM\OneToMany(targetEntity: Libro::class, mappedBy: 'categoria')]
    private Collection $categoria;

    public function __construct()
    {
        $this->categoria = new ArrayCollection();
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
    public function getCategoria(): Collection
    {
        return $this->categoria;
    }

    public function addCategorium(Libro $categorium): static
    {
        if (!$this->categoria->contains($categorium)) {
            $this->categoria->add($categorium);
            $categorium->setCategoria($this);
        }

        return $this;
    }

    public function removeCategorium(Libro $categorium): static
    {
        if ($this->categoria->removeElement($categorium)) {
            // set the owning side to null (unless already changed)
            if ($categorium->getCategoria() === $this) {
                $categorium->setCategoria(null);
            }
        }

        return $this;
    }
}
