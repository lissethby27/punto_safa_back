<?php

namespace App\Repository;

use App\Entity\Autor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Autor>
 */
class AutorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Autor::class);
    }
    public function buscarPorNombreParcial(string $nombre)
    {
        return $this->createQueryBuilder('a')
            ->where('a.nombre LIKE :nombre')
            ->setParameter('nombre', "%$nombre%")
            ->getQuery()
            ->getResult();
    }


}
