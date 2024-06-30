<?php

namespace Sbooker\CommandBus\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\ForUpdate\ConflictResolutionMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\Mapping\MappingException;
use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\CleanStorage;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\ReadStorage;
use Sbooker\CommandBus\Status;
use Sbooker\CommandBus\WriteStorage;

final class DoctrineRepository extends EntityRepository implements WriteStorage, ReadStorage, CleanStorage
{
    public function get(UuidInterface $id): ?Command
    {
        return $this->find($id);
    }

    /**
     * @throws ORMException
     * @throws \ReflectionException
     * @throws MappingException
     */
    public function getAndLock(array $names, UuidInterface $id): ?Command
    {
        $alias = 'c';
        $qb = $this->createDbalQueryBuilderWithCommonExpression($alias, $names);
        $sql = $qb
            ->andWhere('c.id = :id')
            ->getSQL();

        return $this->findCommandBySQL($alias, $sql, ['id' => $id->toString()]);
    }

    /**
     * @throws ORMException
     * @throws \ReflectionException
     * @throws MappingException|DBALException
     */
    public function getFirstToProcessAndLock(array $names): ?Command
    {
        $alias = 'c';
        $qb = $this->createDbalQueryBuilderWithCommonExpression($alias, $names);

        $sql = $qb
            ->andWhere(
                $this->buildInExpr(
                    $qb->expr(),
                    'c.status',
                    [
                        Status::created()->getRawValue(),
                        Status::pending()->getRawValue()
                    ]
                )
            )
            ->andWhere('c.next_attempt_at < :now')
            ->orderBy('c.next_attempt_at', 'ASC')
            ->setMaxResults(1)
            ->getSQL()
        ;

        return $this->findCommandBySQL($alias, $sql, [
            'now' => (new \DateTimeImmutable())->format($this->getPlatform()->getDateTimeTzFormatString()),
        ]);
    }

    /**
     * @throws \ReflectionException
     * @throws MappingException
     */
    private function createDbalQueryBuilderWithCommonExpression(string $alias, array $names): QueryBuilder
    {
        $qb = $this->getConnection()->createQueryBuilder();
        $qb
            ->select("$alias.*")
            ->from($this->getTableName(), $alias)
            ->forUpdate(ConflictResolutionMode::SKIP_LOCKED)
        ;

        if ([] !== $names) {
            $qb->andWhere($this->buildInExpr($qb->expr(), "$alias.name", $names));
        }

        return $qb;
    }

    private function buildInExpr(ExpressionBuilder $expr, string $field, array $values): string
    {
        if ([] === $values) {
            throw new \InvalidArgumentException("Parameter values must not be empty");
        }

        return
            $expr->in(
                $field,
                array_map(
                    fn(string $name) => $expr->literal($name, ParameterType::STRING),
                    $values
                )
            );
    }

    /**
     * @throws NonUniqueResultException
     */
    private function findCommandBySQL(string $tableAlias, string $sql, array $parameters): ?Command
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Command::class, $tableAlias);

        return
            $this->getEntityManager()
                ->createNativeQuery($sql, $rsm)
                ->setParameters($parameters)
                ->getOneOrNullResult();
    }

    /**
     * @throws MappingException
     * @throws \ReflectionException
     */
    private function getTableName(): string
    {
        return $this->getEntityManager()->getMetadataFactory()->getMetadataFor(Command::class)->getTableName();
    }

    /**
     * @throws DBALException
     */
    private function getPlatform(): AbstractPlatform
    {
        return $this->getConnection()->getDatabasePlatform();
    }

    private function getConnection(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    /**
     * @throws DBALException
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function cleanSuccessCommands(\DateTimeImmutable $before): void
    {
        $this->clean(Status::success(), $before);
    }

    /**
     * @throws DBALException
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function cleanFailedCommands(\DateTimeImmutable $before): void
    {
        $this->clean(Status::fail(), $before);
    }

    /**
     * @throws DBALException
     * @throws MappingException
     * @throws \ReflectionException
     */
    private function clean(Status $status, \DateTimeImmutable $before): void
    {
        $qb = $this->getConnection()->createQueryBuilder();

        $qb->delete($this->getTableName(), 'c')
            ->andWhere('c.status = :status')
            ->andWhere('c.next_attempt_at < :before')
            ->setParameter('status', $status->getRawValue())
            ->setParameter('before', $before->format($this->getPlatform()->getDateTimeTzFormatString()))
            ->executeStatement();
    }
}