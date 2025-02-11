<?php

namespace App\Repository;

use App\Entity\Libro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Libro>
 */
class LibroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Libro::class);
    }

    public  function ordenarLibros(string $ordenarPor, int $page=1, int $limit=9, ?\DateTime $fecha = null){
        $queyBuilder = $this->createQueryBuilder('libro')
            ->leftJoin('libro.autor', 'a') // 🔹 Join the author table
            ->addSelect('a');

        switch ($ordenarPor) {
            case 'precio':
                $queyBuilder->orderBy('libro.precio', 'ASC'); // Or 'DESC' for descending order
                break;
            case 'autor':
                $queyBuilder->orderBy('a.nombre', 'ASC');
                break;
            case 'fecha':
                $queyBuilder->orderBy('libro.anio_publicacion', 'ASC');
                break;
            default:
                $queyBuilder->orderBy('libro.titulo', 'ASC');
                break;
        }

        if ($fecha !== null) {
            $queyBuilder->andWhere('YEAR(libro.anio_publicacion) = :fecha')
                ->setParameter('fecha', $fecha->format('Y')); // 🔹 Compare only the year
        }

        if ($page > 0) {
            $queyBuilder->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit);
        }

//        dd($queyBuilder->getQuery()->getSQL());


        return $queyBuilder->getQuery()->getArrayResult();

    }







    //    /**
    //     * @return Libro[] Returns an array of Libro objects
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

    //    public function findOneBySomeField($value): ?Libro
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
