<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Rubrik;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @param Rubrik $rubrik
     * @return Post[]
     */
    
    public function findByRubrik(Rubrik $rubrik): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.rubrik = :rubrik')
            ->setParameter('rubrik', $rubrik)
            ->getQuery()
            ->getResult();
    }  

}
