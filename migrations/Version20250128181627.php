<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250128181627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE public.libro DROP CONSTRAINT fk_libro_autor');
        $this->addSql('ALTER TABLE public.libro DROP CONSTRAINT fk_libro_categoria');
        $this->addSql('ALTER TABLE public.pedido DROP CONSTRAINT fk_pedido_cliente');
        $this->addSql('ALTER TABLE public.resena DROP CONSTRAINT fk_resena_libro');
        $this->addSql('ALTER TABLE public.resena DROP CONSTRAINT fk_resena_usuario');
        $this->addSql('ALTER TABLE public.linea_pedido DROP CONSTRAINT fk_linea_pedido_libro');
        $this->addSql('ALTER TABLE public.linea_pedido DROP CONSTRAINT fk_linea_pedido_pedido');
        $this->addSql('ALTER TABLE public.cliente DROP CONSTRAINT fk_cliente_usuario');
        $this->addSql('DROP TABLE public.libro');
        $this->addSql('DROP TABLE public.pedido');
        $this->addSql('DROP TABLE public.resena');
        $this->addSql('DROP TABLE public.linea_pedido');
        $this->addSql('DROP TABLE public.categoria');
        $this->addSql('DROP TABLE public.cliente');
        $this->addSql('DROP TABLE public.autor');
        $this->addSql('DROP TABLE public.usuario');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SCHEMA puntosafa');
        $this->addSql('CREATE SCHEMA safe_hand');
        $this->addSql('CREATE SCHEMA sabores');
        $this->addSql('CREATE TABLE public.libro (id SERIAL NOT NULL, id_autor INT NOT NULL, id_categoria INT NOT NULL, titulo VARCHAR(255) NOT NULL, resumen VARCHAR(800) DEFAULT NULL, anio_publicacion DATE NOT NULL, precio DOUBLE PRECISION NOT NULL, isbn VARCHAR(20) NOT NULL, editorial VARCHAR(200) NOT NULL, imagen VARCHAR(500) NOT NULL, idioma VARCHAR(100) DEFAULT NULL, num_paginas INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX libro_isbn_key ON public.libro (isbn)');
        $this->addSql('CREATE INDEX IDX_6648CC4FDF821F8A ON public.libro (id_autor)');
        $this->addSql('CREATE INDEX IDX_6648CC4FCE25AE0A ON public.libro (id_categoria)');
        $this->addSql('CREATE TABLE public.pedido (id SERIAL NOT NULL, id_cliente INT NOT NULL, fecha TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, total DOUBLE PRECISION NOT NULL, estado VARCHAR(100) DEFAULT \'procesado\', direccion_entrega VARCHAR(200) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8E0262EE2A813255 ON public.pedido (id_cliente)');
        $this->addSql('CREATE TABLE public.resena (id SERIAL NOT NULL, id_libro INT NOT NULL, id_usuario INT NOT NULL, calificacion INT NOT NULL, comentario VARCHAR(200) NOT NULL, fecha TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1A49902AB91CEC1B ON public.resena (id_libro)');
        $this->addSql('CREATE INDEX IDX_1A49902AFCF8192D ON public.resena (id_usuario)');
        $this->addSql('CREATE TABLE public.linea_pedido (id SERIAL NOT NULL, id_libro INT NOT NULL, id_pedido INT NOT NULL, cantidad INT NOT NULL, precio_unitario DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_586F4C41B91CEC1B ON public.linea_pedido (id_libro)');
        $this->addSql('CREATE INDEX IDX_586F4C41E2DBA323 ON public.linea_pedido (id_pedido)');
        $this->addSql('CREATE TABLE public.categoria (id SERIAL NOT NULL, nombre VARCHAR(100) NOT NULL, descripcion VARCHAR(800) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE public.cliente (id SERIAL NOT NULL, id_usuario INT NOT NULL, nombre VARCHAR(100) NOT NULL, apellidos VARCHAR(100) NOT NULL, dni VARCHAR(100) NOT NULL, foto VARCHAR(255) NOT NULL, direccion VARCHAR(200) NOT NULL, telefono VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX cliente_dni_key ON public.cliente (dni)');
        $this->addSql('CREATE INDEX IDX_CF385599FCF8192D ON public.cliente (id_usuario)');
        $this->addSql('CREATE TABLE public.autor (id SERIAL NOT NULL, nombre VARCHAR(100) NOT NULL, apellidos VARCHAR(100) NOT NULL, biografia VARCHAR(800) NOT NULL, nacionalidad VARCHAR(100) DEFAULT NULL, fecha_nacimiento DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE public.usuario (id SERIAL NOT NULL, nick VARCHAR(100) NOT NULL, contrasena VARCHAR(255) NOT NULL, rol VARCHAR(100) DEFAULT \'cliente\', email VARCHAR(150) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX usuario_email_key ON public.usuario (email)');
        $this->addSql('ALTER TABLE public.libro ADD CONSTRAINT fk_libro_autor FOREIGN KEY (id_autor) REFERENCES public.autor (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE public.libro ADD CONSTRAINT fk_libro_categoria FOREIGN KEY (id_categoria) REFERENCES public.categoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE public.pedido ADD CONSTRAINT fk_pedido_cliente FOREIGN KEY (id_cliente) REFERENCES public.cliente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE public.resena ADD CONSTRAINT fk_resena_libro FOREIGN KEY (id_libro) REFERENCES public.libro (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE public.resena ADD CONSTRAINT fk_resena_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE public.linea_pedido ADD CONSTRAINT fk_linea_pedido_libro FOREIGN KEY (id_libro) REFERENCES public.libro (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE public.linea_pedido ADD CONSTRAINT fk_linea_pedido_pedido FOREIGN KEY (id_pedido) REFERENCES public.pedido (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE public.cliente ADD CONSTRAINT fk_cliente_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
