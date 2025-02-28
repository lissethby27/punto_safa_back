<?php

namespace App\Repository;

use App\Entity\Pedido;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pedido>
 */
class PedidoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pedido::class);
    }


    //Alba para probar reseñas
    public function findByUsuario(int $usuarioId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.usuario = :usuarioId')
            ->setParameter('usuarioId', $usuarioId)
            ->getQuery()
            ->getResult();
    }

    public function totalPedidosByCliente($clienteId)
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->Where('p.cliente = :clienteId')
            ->setParameter('clienteId', $clienteId)
            ->getQuery()
            ->getSingleScalarResult();

    }

    public function deliveredPedidosByCliente($clienteId)
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->Where('p.cliente = :clienteId')
            ->andWhere('p.estado = :estado')
            ->setParameter('clienteId', $clienteId)
            ->setParameter('estado', 'entregado')
            ->getQuery()
            ->getSingleScalarResult();

    }

    public function processedPedidosByCliente($clienteId)
    {

        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->Where('p.cliente = :clienteId')
            ->andWhere('p.estado = :estado')
            ->setParameter('clienteId', $clienteId)
            ->setParameter('estado', 'procesado')
            ->getQuery()
            ->getSingleScalarResult();

    }

    //    /**
    //     * @return Pedido[] Returns an array of Pedido objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Pedido
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
