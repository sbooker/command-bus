<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Persistence;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\ReadStorage;
use Sbooker\CommandBus\WriteStorage;
use Sbooker\CommandBus\Status;
use Doctrine\DBAL\LockMode;
use Ramsey\Uuid\UuidInterface;

class DoctrineRepository extends EntityRepository implements WriteStorage, ReadStorage
{
    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getAndLock(array $names, UuidInterface $id): ?Command
    {
        return
            $this->createQueryBuilderWithNamesCondition('t', $names)
                ->andWhere('t.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult()
            ;
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function get(UuidInterface $id): ?Command
    {
        return $this->find($id);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getFirstToProcessAndLock(array $names): ?Command
    {
        $builder = $this->createQueryBuilderWithNamesCondition('t', $names);
        $expr = $builder->expr();

        return
            $builder
                ->andWhere(
                    $expr->in(
                        "t.workflow.status",
                        [
                            Status::created()->getRawValue(),
                            Status::pending()->getRawValue()
                        ]
                    )
                )
                ->andWhere('t.attemptCounter.nextAttemptAt < :now')
                ->orderBy('t.attemptCounter.nextAttemptAt', 'ASC')
                ->setParameter('now', new \DateTimeImmutable())
                ->setMaxResults(1)
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();
    }

    private function createQueryBuilderWithNamesCondition(string $alias, array $names): QueryBuilder
    {
        $builder = $this->createQueryBuilder($alias);

        if ([] === $names) {
            return $builder;
        }

        $expr = $builder->expr();
        $builder->andWhere($expr->in("$alias.normalizedCommand.name", $names));

        return $builder;
    }
}