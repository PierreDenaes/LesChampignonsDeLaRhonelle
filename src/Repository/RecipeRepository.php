<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    public function findRecipeWithDetails($id)
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.ingredients', 'i')
            ->addSelect('i')
            ->leftJoin('r.steps', 's')
            ->addSelect('s')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestRecipes(int $limit): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
