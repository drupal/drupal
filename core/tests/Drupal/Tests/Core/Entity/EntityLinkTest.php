<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Link;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityBase
 * @group Entity
 */
class EntityLinkTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The tested link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $linkGenerator;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->linkGenerator = $this->createMock('Drupal\Core\Utility\LinkGeneratorInterface');
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('link_generator', $this->linkGenerator);
    $container->set('language_manager', $this->languageManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests for the Entity::toLink() method.
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

    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLinkTemplates')
      ->willReturn($route_name_map);
    $entity_type->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        ['label', 'label'],
        ['langcode', 'langcode'],
      ]);

    $this->entityTypeManager
      ->expects($this->any())
      ->method('getDefinition')
      ->with($entity_type_id)
      ->willReturn($entity_type);

    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $this->getMockForAbstractClass(ConfigEntityBase::class, [
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
