<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityUrlTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity
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
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('url_generator', $this->urlGenerator);
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
    $entity = $this->getMockForAbstractClass($entity_class, array(array('id' => 'test_entity_id'), 'test_entity_type'));
    $uri = $this->getTestUrlInfo($entity, $link_template);

    $this->assertSame($expected, $uri->getRouteName());
    $this->assertSame($entity, $uri->getOption('entity'));
  }

  /**
   * Provides test data for testUrlInfo().
   */
  public function providerTestUrlInfo() {
    return array(
      array('Drupal\Core\Entity\Entity', 'edit-form', 'test_entity_type.edit'),
      array('Drupal\Core\Config\Entity\ConfigEntityBase', 'edit-form', 'test_entity_type.edit'),
      // Test that overriding the default $rel parameter works.
      array('Drupal\Core\Config\Entity\ConfigEntityBase', FALSE, 'test_entity_type.edit'),
    );
  }

  /**
   * Tests the urlInfo() method with an invalid link template.
   *
   * @covers ::urlInfo()
   *
   * @expectedException \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   * @expectedExceptionMessage No link template "canonical" found for the "test_entity_type" entity type
   *
   * @dataProvider providerTestUrlInfoForInvalidLinkTemplate
   */
  public function testUrlInfoForInvalidLinkTemplate($entity_class, $link_template) {
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    $entity = $this->getMockForAbstractClass($entity_class, array(array('id' => 'test_entity_id'), 'test_entity_type'));
    $uri = $this->getTestUrlInfo($entity, $link_template);

    $this->assertEmpty($uri);
  }

  /**
   * Provides test data for testUrlInfoForInvalidLinkTemplate().
   */
  public function providerTestUrlInfoForInvalidLinkTemplate() {
    return array(
      array('Drupal\Core\Entity\Entity', 'canonical'),
      array('Drupal\Core\Entity\Entity', FALSE),
      array('Drupal\Core\Config\Entity\ConfigEntityBase', 'canonical'),
    );
  }

  /**
   * Creates a \Drupal\Core\Url object based on the entity and link template.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The test entity.
   * @param string $link_template
   *   The link template.
   *
   * @return \Drupal\Core\Url
   *   The URL for this entity's link template.
   */
  protected function getTestUrlInfo(EntityInterface $entity, $link_template) {
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

    return $uri;
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
    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array(), 'test_entity_type'));
    $entity->urlInfo();
  }

  /**
   * Tests the url() method.
   *
   * @covers ::url()
   */
  public function testUrl() {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->exactly(5))
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'canonical' => 'test_entity_type.view',
      )));

    $this->entityManager
      ->expects($this->exactly(5))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $invalid_entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array(), 'test_entity_type'));
    $this->assertSame('', $invalid_entity->url());

    $no_link_entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'));
    $this->assertSame('', $no_link_entity->url('banana'));

    $valid_entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'));
    $this->urlGenerator->expects($this->exactly(2))
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
    $entity_type->expects($this->exactly(2))
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'admin-form' => 'test_entity_type.admin_form',
      )));
    $entity_type->expects($this->exactly(2))
      ->method('getBundleEntityType')
      ->will($this->returnValue('test_entity_type_bundle'));

    $this->entityManager
      ->expects($this->exactly(4))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('test_entity_type.admin_form', array(
        'test_entity_type_bundle' => 'test_entity_bundle',
        'test_entity_type' => 'test_entity_id',
      ))
      ->will($this->returnValue('entity/test_entity_type/test_entity_bundle/test_entity_id'));

    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'), '', TRUE, TRUE, TRUE, array('bundle'));
    $entity->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('test_entity_bundle'));

    $this->assertSame('entity/test_entity_type/test_entity_bundle/test_entity_id', $entity->url('admin-form'));
  }

  /**
   * Tests the getPathByAlias() method.
   *
   * @covers ::getSystemPath()
   */
  public function testGetSystemPath() {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->exactly(3))
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'canonical' => 'test_entity_type.view',
      )));

    $this->entityManager
      ->expects($this->exactly(3))
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $no_link_entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'));
    $this->assertSame('', $no_link_entity->getSystemPath('banana'));

    $this->urlGenerator->expects($this->once())
      ->method('getPathFromRoute')
      ->with('test_entity_type.view', array('test_entity_type' => 'test_entity_id'))
      ->will($this->returnValue('entity/test_entity_type/test_entity_id'));

    $valid_entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'));

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
  public function testLinkTemplates($override_templates, $expected) {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'canonical' => 'test_entity_type.view',
      )));

    $this->entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'), '', TRUE, TRUE, TRUE, array('linkTemplates'));
    $entity->expects($this->any())
      ->method('linkTemplates')
      ->will($this->returnCallback(function () use ($entity_type, $override_templates) {
        $templates = $entity_type->getLinkTemplates();
        if ($override_templates) {
          $templates['bananas'] = 'test_entity_type.bananas';
        }
        return $templates;
      }));
    $this->assertSame($expected['canonical'], $entity->hasLinkTemplate('canonical'));
    $this->assertSame($expected['bananas'], $entity->hasLinkTemplate('bananas'));
  }

  /**
   * Provides test data for testLinkTemplates().
   */
  public function providerTestLinkTemplates() {
    return array(
      array(FALSE, array(
        'canonical' => TRUE,
        'bananas' => FALSE,
      )),
      array(TRUE, array(
        'canonical' => TRUE,
        'bananas' => TRUE,
      )),
    );
  }

}
