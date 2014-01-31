<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityUrlTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the \Drupal\Core\Entity\EntityInterface URL methods.
 *
 * @coversDefaultClass \Drupal\Core\Entity\Entity
 *
 * @group Drupal
 * @group Entity
 */
class EntityUrlTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'EntityInterface URL test',
      'description' => 'Unit test the EntityInterface URL methods.',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the urlInfo() method.
   *
   * @covers ::urlInfo()
   *
   * @dataProvider providerTestUrlInfo
   */
  public function testUrlInfo($entity_class, $link_template, $expected) {
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    $entity = new $entity_class(array('id' => 'test_entity_id'), 'test_entity_type');

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'edit-form' => 'test_entity_type.edit',
      )));

    $this->entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    // If no link template is given, call without a value to test the default.
    if ($link_template) {
      $uri = $entity->urlInfo($link_template);
    }
    else {
      $uri = $entity->urlInfo();
    }

    if ($expected) {
      $this->assertSame($expected, $uri['route_name']);
      $this->assertSame($entity, $uri['options']['entity']);
    }
    else {
      $this->assertEmpty($uri);
    }
  }

  /**
   * Provides test data for testUrlInfo().
   */
  public function providerTestUrlInfo() {
    return array(
      array('Drupal\Tests\Core\Entity\TestEntity', 'canonical', FALSE),
      array('Drupal\Tests\Core\Entity\TestEntity', 'edit-form', 'test_entity_type.edit'),
      array('Drupal\Tests\Core\Entity\TestEntity', FALSE, FALSE),
      array('Drupal\Tests\Core\Entity\TestConfigEntity', 'canonical', FALSE),
      array('Drupal\Tests\Core\Entity\TestConfigEntity', 'edit-form', 'test_entity_type.edit'),
      // Test that overriding the default $rel parameter works.
      array('Drupal\Tests\Core\Entity\TestConfigEntity', FALSE, 'test_entity_type.edit'),
    );
  }

  /**
   * Tests the urlInfo() method when an entity is still "new".
   *
   * @see \Drupal\Core\Entity\EntityInterface::isNew()
   *
   * @covers ::urlInfo()
   *
   * @expectedException \Drupal\Core\Entity\EntityMalformedException
   */
  public function testUrlInfoForNewEntity() {
    $entity = new TestEntity(array(), 'test_entity_type');
    $entity->urlInfo();
  }

  /**
   * Tests the url() method.
   *
   * @covers ::url()
   */
  public function testUrl() {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->exactly(3))
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'canonical' => 'test_entity_type.view',
      )));

    $this->entityManager
      ->expects($this->exactly(4))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $invalid_entity = new TestEntity(array(), 'test_entity_type');
    $this->assertSame('', $invalid_entity->url());

    $no_link_entity = new TestEntity(array('id' => 'test_entity_id'), 'test_entity_type');
    $this->assertSame('', $no_link_entity->url('banana'));

    $valid_entity = new TestEntity(array('id' => 'test_entity_id'), 'test_entity_type');
    $url_generator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $valid_entity->setUrlGenerator($url_generator);
    $url_generator->expects($this->exactly(2))
      ->method('generateFromRoute')
      ->will($this->returnValueMap(array(
        array(
          'test_entity_type.view',
          array('test_entity_type' => 'test_entity_id'),
          array('entity_type' => 'test_entity_type', 'entity' => $valid_entity),
          '/entity/test_entity_type/test_entity_id',
        ),
        array(
          'test_entity_type.view',
          array('test_entity_type' => 'test_entity_id'),
          array('absolute' => TRUE, 'entity_type' => 'test_entity_type', 'entity' => $valid_entity),
          'http://drupal/entity/test_entity_type/test_entity_id',
        ),
      )));

    $this->assertSame('/entity/test_entity_type/test_entity_id', $valid_entity->url());
    $this->assertSame('http://drupal/entity/test_entity_type/test_entity_id', $valid_entity->url('canonical', array('absolute' => TRUE)));
  }

  /**
   * Tests the url() method for "admin-form".
   *
   * @covers ::urlRouteParameters()
   */
  public function testUrlForAdminForm() {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'admin-form' => 'test_entity_type.admin_form',
      )));
    $entity_type->expects($this->exactly(2))
      ->method('getBundleEntityType')
      ->will($this->returnValue('test_entity_type_bundle'));

    $this->entityManager
      ->expects($this->exactly(3))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $url_generator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $url_generator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_entity_type.admin_form', array(
        'test_entity_type_bundle' => 'test_entity_bundle',
        'test_entity_type' => 'test_entity_id',
      ))
      ->will($this->returnValue('entity/test_entity_type/test_entity_bundle/test_entity_id'));

    $entity = new TestEntityWithBundle(array('id' => 'test_entity_id', 'bundle' => 'test_entity_bundle'), 'test_entity_type');
    $entity->setUrlGenerator($url_generator);

    $this->assertSame('entity/test_entity_type/test_entity_bundle/test_entity_id', $entity->url('admin-form'));
  }

  /**
   * Tests the getSystemPath() method.
   *
   * @covers ::getSystemPath()
   */
  public function testGetSystemPath() {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->exactly(2))
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'canonical' => 'test_entity_type.view',
      )));

    $this->entityManager
      ->expects($this->exactly(3))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $no_link_entity = new TestEntity(array('id' => 'test_entity_id'), 'test_entity_type');
    $this->assertSame('', $no_link_entity->getSystemPath('banana'));

    $url_generator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $url_generator->expects($this->once())
      ->method('getPathFromRoute')
      ->with('test_entity_type.view', array('test_entity_type' => 'test_entity_id'))
      ->will($this->returnValue('entity/test_entity_type/test_entity_id'));

    $valid_entity = new TestEntity(array('id' => 'test_entity_id'), 'test_entity_type');
    $valid_entity->setUrlGenerator($url_generator);

    $this->assertSame('entity/test_entity_type/test_entity_id', $valid_entity->getSystemPath());
  }

  /**
   * Tests the retrieval of link templates.
   *
   * @covers ::hasLinkTemplate()
   * @covers ::linkTemplates()
   *
   * @dataProvider providerTestLinkTemplates
   */
  public function testLinkTemplates($entity_class, $expected) {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->exactly(2))
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'canonical' => 'test_entity_type.view',
      )));

    $this->entityManager
      ->expects($this->exactly(2))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $entity = new $entity_class(array('id' => 'test_entity_id'), 'test_entity_type');
    $this->assertSame($expected['canonical'], $entity->hasLinkTemplate('canonical'));
    $this->assertSame($expected['bananas'], $entity->hasLinkTemplate('bananas'));
  }

  /**
   * Provides test data for testLinkTemplates().
   */
  public function providerTestLinkTemplates() {
    return array(
      array('Drupal\Tests\Core\Entity\TestEntity', array(
        'canonical' => TRUE,
        'bananas' => FALSE,
      )),
      array('Drupal\Tests\Core\Entity\TestEntityWithTemplates', array(
        'canonical' => TRUE,
        'bananas' => TRUE,
      )),
    );
  }

}

class TestConfigEntity extends ConfigEntityBase {
}

class TestEntity extends Entity {

  /**
   * Sets the URL generator.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *
   * @return $this
   */
  public function setUrlGenerator(UrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
    return $this;
  }

}

class TestEntityWithTemplates extends TestEntity {

  /**
   * {@inheritdoc}
   */
  protected function linkTemplates() {
    $templates = parent::linkTemplates();
    $templates['bananas'] = 'test_entity_type.bananas';
    return $templates;
  }

}

class TestEntityWithBundle extends TestEntity {

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return $this->bundle;
  }

}
