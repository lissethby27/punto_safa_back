<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Usuario>
 */
class UsuarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usuario::class);
    }


    public function findOneByUsuario($usuario)
    {
        return $this->findOneBy(['usuario' => $usuario]);
    }

    // UsuarioRepository.php
    public function findOneByNick(string $nick): ?Usuario
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.nick = :nick')
            ->setParameter('nick', $nick)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
