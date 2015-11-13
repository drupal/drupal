<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityLinkTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Link;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity
 * @group Entity
 */
class EntityLinkTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The tested link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $linkGenerator;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->linkGenerator = $this->getMock('Drupal\Core\Utility\LinkGeneratorInterface');
    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('link_generator', $this->linkGenerator);
    $container->set('language_manager', $this->languageManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests for the Entity::link() method
   *
   * @covers ::link
   *
   * @dataProvider providerTestLink
   */
  public function testLink($entity_label, $link_text, $expected_text, $link_rel = 'canonical', array $link_options = []) {
    $language = new Language(['id' => 'es']);
    $link_options += ['language' => $language];
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with('es')
      ->willReturn($language);

    $route_name_map = [
      'canonical' => 'entity.test_entity_type.canonical',
      'edit-form' => 'entity.test_entity_type.edit_form',
    ];
    $route_name = $route_name_map[$link_rel];
    $entity_id = 'test_entity_id';
    $entity_type_id = 'test_entity_type';
    $expected = '<a href="/test_entity_type/test_entity_id">' . $expected_text . '</a>';

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLinkTemplates')
      ->willReturn($route_name_map);
    $entity_type->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        ['label', 'label'],
        ['langcode', 'langcode'],
      ]);

    $this->entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->with($entity_type_id)
      ->will($this->returnValue($entity_type));

    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', [
      ['id' => $entity_id, 'label' => $entity_label, 'langcode' => 'es'],
      $entity_type_id,
    ]);

    $expected_link = Link::createFromRoute(
      $expected_text,
      $route_name,
      [$entity_type_id => $entity_id],
      ['entity_type' => $entity_type_id, 'entity' => $entity] + $link_options
    )->setLinkGenerator($this->linkGenerator);

    $this->linkGenerator->expects($this->once())
      ->method('generateFromLink')
      ->with($this->equalTo($expected_link))
      ->willReturn($expected);

    $this->assertSame($expected, $entity->link($link_text, $link_rel, $link_options));
  }

  /**
   * Tests for the Entity::toLink() method
   *
   * @covers ::toLink
   *
   * @dataProvider providerTestLink
   */
  public function testToLink($entity_label, $link_text, $expected_text, $link_rel = 'canonical', array $link_options = []) {
    $language = new Language(['id' => 'es']);
    $link_options += ['language' => $language];
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with('es')
      ->willReturn($language);

    $route_name_map = [
      'canonical' => 'entity.test_entity_type.canonical',
      'edit-form' => 'entity.test_entity_type.edit_form',
    ];
    $route_name = $route_name_map[$link_rel];
    $entity_id = 'test_entity_id';
    $entity_type_id = 'test_entity_type';
    $expected = '<a href="/test_entity_type/test_entity_id">' . $expected_text . '</a>';

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLinkTemplates')
      ->willReturn($route_name_map);
    $entity_type->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        ['label', 'label'],
        ['langcode', 'langcode'],
      ]);

    $this->entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->with($entity_type_id)
      ->will($this->returnValue($entity_type));

    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', [
      ['id' => $entity_id, 'label' => $entity_label, 'langcode' => 'es'],
      $entity_type_id,
    ]);

    $expected_link = Link::createFromRoute(
      $expected_text,
      $route_name,
      [$entity_type_id => $entity_id],
      ['entity_type' => $entity_type_id, 'entity' => $entity] + $link_options
    );

    $result_link = $entity->toLink($link_text, $link_rel, $link_options);
    $this->assertEquals($expected_link, $result_link);
  }

  /**
   * Provides test data for testLink().
   */
  public function providerTestLink() {
    $data = [];
    $data[] = [
      'some_entity_label',
      'qwerqwer',
      'qwerqwer',
    ];
    $data[] = [
      'some_entity_label',
      NULL,
      'some_entity_label',
    ];
    $data[] = [
      'some_entity_label',
      '0',
      '0',
    ];
    $data[] = [
      'some_entity_label',
      'qwerqwer',
      'qwerqwer',
      'edit-form',
    ];
    $data[] = [
      'some_entity_label',
      'qwerqwer',
      'qwerqwer',
      'edit-form',
    ];
    $data[] = [
      'some_entity_label',
      'qwerqwer',
      'qwerqwer',
      'edit-form',
      ['foo' => 'qwer'],
    ];
    return $data;
  }

}
