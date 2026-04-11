<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time)
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @param int $userId
     * @return User|null
     * Fetches a user with their wishlist products and categories in a single query (the N+1 problem)
     */
    public function findWithWishlistAndCategories(int $userId): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.wishlists' , 'p')
            ->addSelect('p')
            ->leftJoin('p.category' , 'c')
            ->addSelect('c')
            ->where('u.id = :id')
            ->setParameter('id' , $userId)
            ->getQuery()
            ->getOneOrNullResult();

    }
}
