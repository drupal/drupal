<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\ConfigEntityBaseUnitTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\ConfigEntityBase
 *
 * @group Drupal
 */
class ConfigEntityBaseUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The entity info used for testing..
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityInfo;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The type of the entity under test.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\Core\Config\Entity\ConfigEntityBase unit test',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $values = array();
    $this->entityType = $this->randomName();

    $this->entityInfo = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityType)
      ->will($this->returnValue($this->entityInfo));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    \Drupal::setContainer($container);

    $this->entity = $this->getMockBuilder('\Drupal\Core\Config\Entity\ConfigEntityBase')
      ->setConstructorArgs(array($values, $this->entityType))
      ->setMethods(array('languageLoad'))
      ->getMock();
    $this->entity->expects($this->any())
      ->method('languageLoad')
      ->will($this->returnValue(NULL));
  }

  /**
   * @covers ::setOriginalId
   * @covers ::getOriginalId
   */
  public function testGetOriginalId() {
    $id = $this->randomName();
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->setOriginalId($id)));
    $this->assertSame($id, $this->entity->getOriginalId());
  }

  /**
   * @covers ::isNew
   */
  public function testIsNew() {
    $this->assertFalse($this->entity->isNew());
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->enforceIsNew()));
    $this->assertTrue($this->entity->isNew());
    $this->entity->enforceIsNew(FALSE);
    $this->assertFalse($this->entity->isNew());
  }

  /**
   * @covers ::set
   * @covers ::get
   */
  public function testGet() {
    $name = $this->randomName();
    $value = $this->randomName();
    $this->assertNull($this->entity->get($name));
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->set($name, $value)));
    $this->assertSame($value, $this->entity->get($name));
  }

  /**
   * @covers ::setStatus
   * @covers ::status
   */
  public function testSetStatus() {
    $this->assertTrue($this->entity->status());
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->setStatus(FALSE)));
    $this->assertFalse($this->entity->status());
    $this->entity->setStatus(TRUE);
    $this->assertTrue($this->entity->status());
  }

  /**
   * @covers ::enable
   * @depends testSetStatus
   */
  public function testEnable() {
    $this->entity->setStatus(FALSE);
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->enable()));
    $this->assertTrue($this->entity->status());
  }

  /**
   * @covers ::disable
   * @depends testSetStatus
   */
  public function testDisable() {
    $this->entity->setStatus(TRUE);
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->disable()));
    $this->assertFalse($this->entity->status());
  }

  /**
   * @covers ::setSyncing
   * @covers ::isSyncing
   */
  public function testIsSyncing() {
    $this->assertFalse($this->entity->isSyncing());
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->setSyncing(TRUE)));
    $this->assertTrue($this->entity->isSyncing());
    $this->entity->setSyncing(FALSE);
    $this->assertFalse($this->entity->isSyncing());
  }

  /**
   * @covers ::createDuplicate
   */
  public function testCreateDuplicate() {
    $this->entityInfo->expects($this->once())
      ->method('getKey')
      ->with('id')
      ->will($this->returnValue('id'));

    $this->entity->setOriginalId('foo');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $duplicate */
    $duplicate = $this->entity->createDuplicate();
    $this->assertInstanceOf('\Drupal\Core\Entity\Entity', $duplicate);
    $this->assertNotSame(spl_object_hash($this->entity), spl_object_hash($duplicate));
    $this->assertNull($duplicate->id());
    $this->assertNull($duplicate->getOriginalId());
    $this->assertNull($duplicate->uuid());
  }

  /**
   * @covers ::sort
   */
  public function testSort() {
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityType)
      ->will($this->returnValue(array(
        'entity_keys' => array(
          'label' => 'label',
        ),
      )));
    $entity_a = $this->entity;
    $entity_a->label = 'foo';
    $entity_b = clone $this->entity;
    $entity_b->label = 'bar';
    $list = array($entity_a, $entity_b);
    // Suppress errors because of https://bugs.php.net/bug.php?id=50688.
    @usort($list, '\Drupal\Core\Config\Entity\ConfigEntityBase::sort');
    $this->assertSame(spl_object_hash($entity_b), spl_object_hash($list[0]));
    $entity_a->weight = 0;
    $entity_b->weight = 1;
    // Suppress errors because of https://bugs.php.net/bug.php?id=50688.
    @usort($list, array($entity_a, 'sort'));
    $this->assertSame(spl_object_hash($entity_a), spl_object_hash($list[0]));
  }

  /**
   * @covers ::getExportProperties
   */
  public function testGetExportProperties() {
    $properties = $this->entity->getExportProperties();
    $this->assertInternalType('array', $properties);
    $class_info = new \ReflectionClass($this->entity);
    foreach ($class_info->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
      $name = $property->getName();
      $this->assertArrayHasKey($name, $properties);
      $this->assertSame($this->entity->get($name), $properties[$name]);
    }
  }
}
