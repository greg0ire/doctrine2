<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Version;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Tests\OrmFunctionalTestCase;

use function sleep;

class GH8499Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->setUpEntitySchema(
                [GH8499VersionableEntity::class]
            );
        } catch (SchemaException $e) {
        }
    }

    /**
     * @group GH-8499
     */
    public function testOptimisticLockInitializesVersionOnEntityInsert(): void
    {
        $entity = new GH8499VersionableEntity();
        $entity->setName('Test Entity');
        $entity->setDescription('Entity to test optimistic lock fix with DateTimeInterface objects');
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->refresh($entity);

        $this->assertInstanceOf(DateTimeInterface::class, $entity->getRevision(), 'Version field not set to DateTimeInterface');
    }

    /**
     * @group GH-8499
     */
    public function testOptimisticLockUpdatesVersionOnEntityUpdate(): void
    {
        $entity = new GH8499VersionableEntity();
        $entity->setName('Test Entity');
        $entity->setDescription('Entity to test optimistic lock fix with DateTimeInterface objects');
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->refresh($entity);
        $firstVersion = $entity->getRevision();
        sleep(1);

        $entity->setName('Test Entity Updated');
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->refresh($entity);
        $lastVersion = $entity->getRevision();

        $this->assertNotEquals($firstVersion, $lastVersion, 'Version field value not updated on persist');
    }

    /**
     * @group GH-8499
     */
    public function testOptimisticLockWithDateTimeForVersion(): void
    {
        $entity = new GH8499VersionableEntity();
        $entity->setName('Test Entity');
        $entity->setDescription('Entity to test optimistic lock fix with DateTimeInterface objects');
        $this->_em->persist($entity);
        $this->_em->flush();

        $firstVersion = $entity->getRevision();

        $this->_em->lock($entity, LockMode::OPTIMISTIC, $firstVersion);
        sleep(1);

        $entity->setName('Test Entity Locked');
        $this->_em->persist($entity);
        $this->_em->flush();

        $this->assertNotEquals($firstVersion, $entity->getRevision(), 'Version field value not updated on persist');
    }

    /**
     * @group GH-8499
     */
    public function testOptimisticLockWithDateTimeForVersionThrowsException(): void
    {
        $this->expectException(OptimisticLockException::class);

        $entity = new GH8499VersionableEntity();
        $entity->setName('Test Entity');
        $entity->setDescription('Entity to test optimistic lock fix with DateTimeInterface objects');
        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->lock($entity, LockMode::OPTIMISTIC, new DateTime('2020-07-15 18:04:00'));
    }
}

/**
 * @Entity
 * @Table
 */
class GH8499VersionableEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @Column(type="string")
     * @var string
     */
    public $name;

    /**
     * @Column(type="string")
     * @var string
     */
    public $description;

    /**
     * @Version
     * @Column(type="datetime")
     * @var DateTimeInterface
     */
    public $revision;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getRevision(): DateTimeInterface
    {
        return $this->revision;
    }
}
