<?php

namespace App\DTO;

class ClienteDTO
{
    private ?int $id = null;
    private ?string $nombre = null;
    private ?string $apellidos = null;
    private ?string $DNI = null;

    private ?string $foto = null;

    private ?string $direccion = null;

    private ?string $telefono = null;

    private ?string $email = null;

    private ?string $nick = null;


    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    /**
     * @param string|null $nombre
     */

    public function setNombre(?string $nombre): void
    {
        $this->nombre = $nombre;
    }

    /**
     * @return string|null
     */

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    /**
     * @param string|null $apellidos
     */

    public function setApellidos(?string $apellidos): void
    {
        $this->apellidos = $apellidos;
    }

    /**
     * @return string|null
     */

    public function getDni(): ?string
    {
        return $this->DNI;
    }


    /**
     * @param string|null $DNI
     */

    public function setDni(?string $DNI): void
    {
        $this->DNI = $DNI;
    }

    /**
     * @return string|null
     */

    public function getFoto(): ?string
    {
        return $this->foto;
    }


    /**
     * @param string|null $foto
     */

    public function setFoto(?string $foto): void
    {
        $this->foto = $foto;
    }

    /**
     * @return string|null
     */

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }


    /**
     * @param string|null $direccion
     */

    public function setDireccion(?string $direccion): void
    {
        $this->direccion = $direccion;
    }

    /**
     * @return string|null
     */

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }


    /**
     * @param string|null $telefono
     */

    public function setTelefono(?string $telefono): void
    {
        $this->telefono = $telefono;
    }

    /**
     * @return string|null
     */

    public function getEmail(): ?string
    {
        return $this->email;
    }


    /**
     * @param string|null $email
     */

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */

    public function getNick(): ?string
    {
        return $this->nick;
    }


    /**
     * @param string|null $nick
     */

    public function setNick(?string $nick): void
    {
        $this->nick = $nick;
    }






}