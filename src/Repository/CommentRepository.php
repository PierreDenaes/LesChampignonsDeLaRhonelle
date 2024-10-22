<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findLatestCommentsByUser($user, int $limit = 3)
    {
        return $this->createQueryBuilder('c')
            ->join('c.recipe', 'r')
            ->where('r.profile = :profile') // Assure-toi que la relation correcte est utilisée (r.profile par exemple)
            ->setParameter('profile', $user->getProfile()) // Selon comment tu associes les utilisateurs aux recettes
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
