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
            ->select('l.id, l.titulo, AVG(r.calificacion) as mediaCalificacion, a.nombre as autorNombre, a.apellidos as autorApellidos, l.imagen as imagen, l.resumen as resumen')
            ->join('r.libro', 'l')
            ->join('l.autor', 'a')  // Relación con el autor
            ->groupBy('l.id, l.titulo, a.nombre, a.apellidos, l.imagen, l.resumen')
            ->orderBy('mediaCalificacion', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }


}
