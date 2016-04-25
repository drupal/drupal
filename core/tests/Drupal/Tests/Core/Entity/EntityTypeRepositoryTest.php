<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepository;
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    $this->entityTypeRepository = new EntityTypeRepository($this->entityTypeManager->reveal());
  }

  /**
   * Sets up the entity type manager to be tested.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[]|\Prophecy\Prophecy\ProphecyInterface[] $definitions
   *   (optional) An array of entity type definitions.
   */
  protected function setUpEntityTypeDefinitions($definitions = []) {
    $class = $this->getMockClass(EntityInterface::class);
    foreach ($definitions as $key => $entity_type) {
      // \Drupal\Core\Entity\EntityTypeInterface::getLinkTemplates() is called
      // by \Drupal\Core\Entity\EntityManager::processDefinition() so it must
      // always be mocked.
      $entity_type->getLinkTemplates()->willReturn([]);

      // Give the entity type a legitimate class to return.
      $entity_type->getClass()->willReturn($class);

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
        else throw new PluginNotFoundException($entity_type_id);
      });
    $this->entityTypeManager->getDefinitions()->willReturn($definitions);
  }

  /**
   * Tests the getEntityTypeLabels() method.
   *
   * @covers ::getEntityTypeLabels
   */
  public function testGetEntityTypeLabels() {
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
  public function testGetEntityTypeFromClass() {
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
   *
   * @expectedException \Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException
   * @expectedExceptionMessage The \Drupal\pear\Entity\Pear class does not correspond to an entity type.
   */
  public function testGetEntityTypeFromClassNoMatch() {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $banana = $this->prophesize(EntityTypeInterface::class);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
      'banana' => $banana,
    ]);

    $apple->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');
    $banana->getOriginalClass()->willReturn('\Drupal\banana\Entity\Banana');

    $this->entityTypeRepository->getEntityTypeFromClass('\Drupal\pear\Entity\Pear');
  }

  /**
   * @covers ::getEntityTypeFromClass
   *
   * @expectedException \Drupal\Core\Entity\Exception\AmbiguousEntityClassException
   * @expectedExceptionMessage Multiple entity types found for \Drupal\apple\Entity\Apple.
   */
  public function testGetEntityTypeFromClassAmbiguous() {
    $boskoop = $this->prophesize(EntityTypeInterface::class);
    $boskoop->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');
    $boskoop->id()->willReturn('boskop');

    $gala = $this->prophesize(EntityTypeInterface::class);
    $gala->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');
    $gala->id()->willReturn('gala');

    $this->setUpEntityTypeDefinitions([
      'boskoop' => $boskoop,
      'gala' => $gala,
    ]);

    $this->entityTypeRepository->getEntityTypeFromClass('\Drupal\apple\Entity\Apple');
  }

}
