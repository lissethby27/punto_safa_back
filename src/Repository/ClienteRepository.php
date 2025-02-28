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

    //    /**
    //     * @return Cliente[] Returns an array of Cliente objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Cliente
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
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
//    public function findOneByUsuario(UserInterface $usuario): ?Cliente
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.usuario = :usuario')  // Asegúrate de que la propiedad 'usuario' exista en la entidad Cliente
//            ->setParameter('usuario', $usuario)
//            ->getQuery()
//            ->getOneOrNullResult();
//    }

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
