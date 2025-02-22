<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Entity\Exception\AmbiguousEntityClassException;
use Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityTypeRepository
 * @group Entity
 */
class EntityTypeRepositoryTest extends UnitTestCase {

  /**
   * The entity type repository under test.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepository
   */
  protected $entityTypeRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);

    $this->entityTypeRepository = new EntityTypeRepository($this->entityTypeManager->reveal(), $this->entityTypeBundleInfo->reveal());
  }

  /**
   * Sets up the entity type manager to be tested.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[]|\Prophecy\Prophecy\ProphecyInterface[] $definitions
   *   (optional) An array of entity type definitions.
   */
  protected function setUpEntityTypeDefinitions($definitions = []): void {
    foreach ($definitions as $key => $entity_type) {
      // \Drupal\Core\Entity\EntityTypeInterface::getLinkTemplates() is called
      // by \Drupal\Core\Entity\EntityTypeManager::processDefinition() so it
      // must always be mocked.
      $entity_type->getLinkTemplates()->willReturn([]);

      // Give the entity type a legitimate class to return.
      $entity_type->getClass()->willReturn(EntityInterface::class);

      $definitions[$key] = $entity_type->reveal();
    }

    $this->entityTypeManager->getDefinition(Argument::cetera())
      ->will(function ($args) use ($definitions) {
        $entity_type_id = $args[0];
        $exception_on_invalid = $args[1];
        if (isset($definitions[$entity_type_id])) {
          return $definitions[$entity_type_id];
        }
        elseif (!$exception_on_invalid) {
          return NULL;
        }
        else {
          throw new PluginNotFoundException($entity_type_id);
        }
      });
    $this->entityTypeManager->getDefinitions()->willReturn($definitions);
    $this->entityTypeBundleInfo->getAllBundleInfo()->willReturn([]);
  }

  /**
   * Tests the getEntityTypeLabels() method.
   *
   * @covers ::getEntityTypeLabels
   */
  public function testGetEntityTypeLabels(): void {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getLabel()->willReturn('Apple');
    $apple->getBundleOf()->willReturn(NULL);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->getLabel()->willReturn('Banana');
    $banana->getBundleOf()->willReturn(NULL);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
      'banana' => $banana,
    ]);

    $expected = [
      'apple' => 'Apple',
      'banana' => 'Banana',
    ];
    $this->assertSame($expected, $this->entityTypeRepository->getEntityTypeLabels());
  }

  /**
   * @covers ::getEntityTypeFromClass
   */
  public function testGetEntityTypeFromClass(): void {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $banana = $this->prophesize(EntityTypeInterface::class);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
      'banana' => $banana,
    ]);

    $apple->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');

    $banana->getOriginalClass()->willReturn('\Drupal\banana\Entity\Banana');
    $banana->getClass()->willReturn('\Drupal\mango\Entity\Mango');
    $banana->id()
      ->willReturn('banana')
      ->shouldBeCalledTimes(2);

    $entity_type_id = $this->entityTypeRepository->getEntityTypeFromClass('\Drupal\banana\Entity\Banana');
    $this->assertSame('banana', $entity_type_id);
    $entity_type_id = $this->entityTypeRepository->getEntityTypeFromClass('\Drupal\mango\Entity\Mango');
    $this->assertSame('banana', $entity_type_id);
  }

  /**
   * @covers ::getEntityTypeFromClass
   */
  public function testGetEntityTypeFromClassNoMatch(): void {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $banana = $this->prophesize(EntityTypeInterface::class);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
      'banana' => $banana,
    ]);

    $apple->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');
    $banana->getOriginalClass()->willReturn('\Drupal\banana\Entity\Banana');

    $this->expectException(NoCorrespondingEntityClassException::class);
    $this->expectExceptionMessage('The \Drupal\pear\Entity\Pear class does not correspond to an entity type.');
    $this->entityTypeRepository->getEntityTypeFromClass('\Drupal\pear\Entity\Pear');
  }

  /**
   * @covers ::getEntityTypeFromClass
   */
  public function testGetEntityTypeFromClassAmbiguous(): void {
    $jazz = $this->prophesize(EntityTypeInterface::class);
    $jazz->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');
    $jazz->id()->willReturn('jazz');

    $gala = $this->prophesize(EntityTypeInterface::class);
    $gala->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');
    $gala->id()->willReturn('gala');

    $this->setUpEntityTypeDefinitions([
      'jazz' => $jazz,
      'gala' => $gala,
    ]);

    $this->expectException(AmbiguousEntityClassException::class);
    $this->expectExceptionMessage('Multiple entity types found for \Drupal\apple\Entity\Apple.');
    $this->entityTypeRepository->getEntityTypeFromClass('\Drupal\apple\Entity\Apple');
  }

  /**
   * @covers ::getEntityTypeFromClass
   */
  public function testGetEntityTypeFromClassAmbiguousBundleClass(): void {
    $blackcurrant = $this->prophesize(EntityTypeInterface::class);
    $blackcurrant->getOriginalClass()->willReturn(Apple::class);
    $blackcurrant->getClass()->willReturn(Blackcurrant::class);
    $blackcurrant->id()->willReturn('blackcurrant');

    $gala = $this->prophesize(EntityTypeInterface::class);
    $gala->getOriginalClass()->willReturn(Apple::class);
    $gala->getClass()->willReturn(RoyalGala::class);
    $gala->id()->willReturn('gala');

    $this->setUpEntityTypeDefinitions([
      'blackcurrant' => $blackcurrant,
      'gala' => $gala,
    ]);

    $this->entityTypeBundleInfo->getAllBundleInfo()->willReturn([
      'gala' => [
        'royal_gala' => [
          'label' => 'Royal Gala',
          'class' => RoyalGala::class,
        ],
      ],
    ]);

    $this->assertSame('gala', $this->entityTypeRepository->getEntityTypeFromClass(RoyalGala::class));
  }

}

/**
 * A simple entity for testing.
 */
class Fruit extends EntityBase {
}

/**
 * A Fruit class for testing.
 */
class Apple extends Fruit {
}

/**
 * An Apple class for testing.
 */
class RoyalGala extends Apple {
}

/**
 * A Fruit class for testing.
 */
class Blackcurrant extends Fruit {
}
