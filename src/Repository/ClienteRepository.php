<?php

namespace App\Repository;

use App\Entity\Cliente;
use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Cliente>
 */
class ClienteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cliente::class);
    }


    public function buscarPorNombreParcial(string $nombre)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nombre LIKE :nombre')
            ->setParameter('nombre', '%' . $nombre . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByUserEmail(string $email): ?Cliente
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.usuario', 'u')  // Relacionamos con la entidad Usuario
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */


    public function findByUserId(int $userId): ?Cliente
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.usuario', 'u')  // Join with Usuario table
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }




    // ClienteRepository.php
    public function findOneByUsuario(Usuario $usuario): ?Cliente
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.usuario = :usuario')
            ->setParameter('usuario', $usuario)
            ->getQuery()
            ->getOneOrNullResult();
    }
    // UsuarioRepository.php
    public function findOneByUsername(string $username): ?Usuario
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }



}
