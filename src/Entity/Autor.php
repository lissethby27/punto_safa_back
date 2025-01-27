<?php

namespace App\Entity;

use App\Repository\AutorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutorRepository::class)]
#[ORM\Table(name: "autor", schema: "puntosafa")]
class Autor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: "nombre", type: Types::STRING, length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(name: "apellidos", type: Types::STRING, length: 100)]
    private ?string $apellidos = null;

    #[ORM\Column(name: "biografia", type: Types::STRING, length: 800)]
    private ?string $biografia = null;

    #[ORM\Column(name: "nacionalidad", type: Types::STRING, length: 100, nullable: true)]
    private ?string $nacionalidad = null;

    #[ORM\Column(name: "fecha_nacimiento", type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fecha_nacimiento = null;

    /**
     * @var Collection<int, Libro>
     */
    #[ORM\OneToMany(targetEntity: Libro::class, mappedBy: 'autor')]
    private Collection $libros;

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

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): static
    {
        $this->apellidos = $apellidos;

        return $this;
    }

    public function getBiografia(): ?string
    {
        return $this->biografia;
    }

    public function setBiografia(string $biografia): static
    {
        $this->biografia = $biografia;

        return $this;
    }

    public function getNacionalidad(): ?string
    {
        return $this->nacionalidad;
    }

    public function setNacionalidad(?string $nacionalidad): static
    {
        $this->nacionalidad = $nacionalidad;

        return $this;
    }

    public function getFechaNacimiento(): ?\DateTimeInterface
    {
        return $this->fecha_nacimiento;
    }

    public function setFechaNacimiento(?\DateTimeInterface $fecha_nacimiento): static
    {
        $this->fecha_nacimiento = $fecha_nacimiento;

        return $this;
    }

    /**
     * @return Collection<int, Libro>
     */
    public function getLibros(): Collection
    {
        return $this->libros;
    }

    public function addLibro(Libro $libro): static
    {
        if (!$this->libros->contains($libro)) {
            $this->libros->add($libro);
            $libro->setAutor($this);
        }

        return $this;
    }

    public function removeLibro(Libro $libro): static
    {
        if ($this->libros->removeElement($libro)) {
            // set the owning side to null (unless already changed)
            if ($libro->getAutor() === $this) {
                $libro->setAutor(null);
            }
        }

        return $this;
    }
}
