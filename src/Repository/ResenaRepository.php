<?php

namespace App\Repository;

use App\Entity\Resena;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resena>
 */
class ResenaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resena::class);
    }

    public function usuarioYaResenoLibro(int $usuarioId, int $libroId): bool
    {
        return (bool) $this->createQueryBuilder('r')
            ->where('r.usuario = :usuarioId')
            ->andWhere('r.libro = :libroId')
            ->setParameter('usuarioId', $usuarioId)
            ->setParameter('libroId', $libroId)
            ->getQuery()
            ->getOneOrNullResult(); // Retorna null si no hay reseña
    }

    public function calcularMediaCalificacionPorLibro(int $id_libro): ?float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('AVG(r.calificacion) as media')
            ->where('r.libro = :id_libro')
            ->setParameter('id_libro', $id_libro)
            ->getQuery();

        return $qb->getSingleScalarResult();
    }

    public function findTopRatedBooks(int $limit = 3): array
    {
        return $this->createQueryBuilder('r')
            ->select('l.id, l.titulo, AVG(r.calificacion) as mediaCalificacion')
            ->join('r.libro', 'l')
            ->groupBy('l.id')
            ->orderBy('mediaCalificacion', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }





    //    /**
    //     * @return Resena[] Returns an array of Resena objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Resena
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
