<?php

namespace App\Repository;

use App\Entity\LineaPedido;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LineaPedido>
 */
class LineaPedidoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LineaPedido::class);
    }

    //Alba para probar reseñas
    public function findByPedido(int $pedidoId): array
    {
        return $this->createQueryBuilder('lp')
            ->andWhere('lp.pedido = :pedidoId')
            ->setParameter('pedidoId', $pedidoId)
            ->getQuery()
            ->getResult();
    }
    // En LineaPedidoRepository.php
    public function findByUsuarioAndLibro(int $usuarioId, int $libroId): array
    {
        return $this->createQueryBuilder('lp')
            ->join('lp.pedido', 'p')
            ->where('p.usuario = :usuarioId')
            ->andWhere('lp.libro = :libroId')
            ->setParameter('usuarioId', $usuarioId)
            ->setParameter('libroId', $libroId)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return LineaPedido[] Returns an array of LineaPedido objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?LineaPedido
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
