<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\DefaultsSectionStorageInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\layout_builder\LayoutEntityHelperTrait
 *
 * @group layout_builder
 */
class LayoutEntityHelperTraitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'entity_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['key_value_expire']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
  }

  /**
   * Dataprovider for testGetSectionStorageForEntity().
   */
  public function providerTestGetSectionStorageForEntity() {
    $data = [];
    $data['entity_view_display'] = [
      'entity_view_display',
      [
        'targetEntityType' => 'entity_test',
        'bundle' => 'entity_test',
        'mode' => 'default',
        'status' => TRUE,
        'third_party_settings' => [
          'layout_builder' => [
            'enabled' => TRUE,
          ],
        ],
      ],
      ['display', 'view_mode'],
    ];
    $data['fieldable entity'] = [
      'entity_test',
      [],
      ['entity', 'display', 'view_mode'],
    ];
    return $data;
  }

  /**
   * @covers ::getSectionStorageForEntity
   *
   * @dataProvider providerTestGetSectionStorageForEntity
   */
  public function testGetSectionStorageForEntity($entity_type_id, $values, $expected_context_keys) {
    $section_storage_manager = $this->prophesize(SectionStorageManagerInterface::class);
    $section_storage_manager->load('')->willReturn(NULL);
    $section_storage_manager->findByContext(Argument::cetera())->will(function ($arguments) {
      return $arguments[0];
    });
    $this->container->set('plugin.manager.layout_builder.section_storage', $section_storage_manager->reveal());
    $entity = $this->container->get('entity_type.manager')->getStorage($entity_type_id)->create($values);
    $entity->save();
    $class = new TestLayoutEntityHelperTrait();
    $result = $class->getSectionStorageForEntity($entity);
    $this->assertEquals($expected_context_keys, array_keys($result));
    if ($entity instanceof EntityViewDisplayInterface) {
      $this->assertEquals(EntityContext::fromEntity($entity), $result['display']);
    }
    elseif ($entity instanceof FieldableEntityInterface) {
      $this->assertEquals(EntityContext::fromEntity($entity), $result['entity']);
      $this->assertInstanceOf(Context::class, $result['view_mode']);
      $this->assertEquals('full', $result['view_mode']->getContextData()->getValue());

      $expected_display = EntityViewDisplay::collectRenderDisplay($entity, 'full');
      $this->assertInstanceOf(EntityContext::class, $result['display']);
      /** @var \Drupal\Core\Plugin\Context\EntityContext $display_entity_context */
      $display_entity_context = $result['display'];

      /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $display_entity */
      $display_entity = $display_entity_context->getContextData()->getValue();
      $this->assertInstanceOf(LayoutBuilderEntityViewDisplay::class, $display_entity);

      $this->assertEquals('full', $display_entity->getMode());
      $this->assertEquals($expected_display->getEntityTypeId(), $display_entity->getEntityTypeId());
      $this->assertEquals($expected_display->getComponents(), $display_entity->getComponents());
      $this->assertEquals($expected_display->getThirdPartySettings('layout_builder'), $display_entity->getThirdPartySettings('layout_builder'));
    }
    else {
      throw new \UnexpectedValueException("Unexpected entity type.");
    }

  }

  /**
   * Dataprovider for testOriginalEntityUsesDefaultStorage().
   */
  public function providerTestOriginalEntityUsesDefaultStorage() {
    return [
      'original uses default' => [
        [
          'updated' => 'override',
          'original' => 'default',
        ],
        FALSE,
        TRUE,
        TRUE,
      ],
      'original uses override' => [
        [
          'updated' => 'override',
          'original' => 'override',
        ],
        FALSE,
        TRUE,
        FALSE,
      ],
      'no original use override' => [
        [
          'updated' => 'override',
        ],
        FALSE,
        FALSE,
        FALSE,
      ],
      'no original uses default' => [
        [
          'updated' => 'default',
        ],
        FALSE,
        FALSE,
        FALSE,
      ],
      'is new use override' => [
        [
          'updated' => 'override',
        ],
        TRUE,
        FALSE,
        FALSE,
      ],
      'is new use default' => [
        [
          'updated' => 'default',
        ],
        TRUE,
        FALSE,
        FALSE,
      ],

    ];
  }

  /**
   * @covers ::originalEntityUsesDefaultStorage
   *
   * @dataProvider providerTestOriginalEntityUsesDefaultStorage
   */
  public function testOriginalEntityUsesDefaultStorage($entity_storages, $is_new, $has_original, $expected) {
    $this->assertFalse($is_new && $has_original);
    $entity = EntityTest::create(['name' => 'updated']);
    if (!$is_new) {
      $entity->save();
      if ($has_original) {
        $original_entity = EntityTest::create(['name' => 'original']);
        $entity->original = $original_entity;
      }

    }

    $section_storage_manager = $this->prophesize(SectionStorageManagerInterface::class);
    $section_storage_manager->load('')->willReturn(NULL);
    $storages = [
      'default' => $this->prophesize(DefaultsSectionStorageInterface::class)->reveal(),
      'override' => $this->prophesize(OverridesSectionStorageInterface::class)->reveal(),
    ];

    $section_storage_manager->findByContext(Argument::cetera())->will(function ($arguments) use ($storages, $entity_storages) {
      $contexts = $arguments[0];
      if (isset($contexts['entity'])) {
        /** @var \Drupal\entity_test\Entity\EntityTest $entity */
        $entity = $contexts['entity']->getContextData()->getValue();
        return $storages[$entity_storages[$entity->getName()]];
      }
    });

    $this->container->set('plugin.manager.layout_builder.section_storage', $section_storage_manager->reveal());
    $class = new TestLayoutEntityHelperTrait();
    $this->assertSame($expected, $class->originalEntityUsesDefaultStorage($entity));
  }

  /**
   * @covers ::getEntitySections
   */
  public function testGetEntitySections() {
    $entity = EntityTest::create(['name' => 'updated']);
    $section_storage_manager = $this->prophesize(SectionStorageManagerInterface::class);
    $section_storage_manager->load('')->willReturn(NULL);
    $section_storage = $this->prophesize(SectionStorageInterface::class);
    $sections = [
      new Section('layout_onecol'),
    ];
    $this->assertCount(1, $sections);
    $section_storage->getSections()->willReturn($sections);
    $section_storage->count()->willReturn(1);

    $section_storage_manager->findByContext(Argument::cetera())->willReturn($section_storage->reveal());
    $this->container->set('plugin.manager.layout_builder.section_storage', $section_storage_manager->reveal());
    $class = new TestLayoutEntityHelperTrait();
    // Ensure that if the entity has a section storage the sections will be
    // returned.
    $this->assertSame($sections, $class->getEntitySections($entity));

    $section_storage_manager->findByContext(Argument::cetera())->willReturn(NULL);
    $this->container->set('plugin.manager.layout_builder.section_storage', $section_storage_manager->reveal());
    // Ensure that if the entity has no section storage an empty array will be
    // returned.
    $this->assertSame([], $class->getEntitySections($entity));
  }

}

/**
 * Test class using the trait.
 */
class TestLayoutEntityHelperTrait {
  use LayoutEntityHelperTrait {
    getSectionStorageForEntity as public;
    originalEntityUsesDefaultStorage as public;
    getEntitySections as public;
  }

}
