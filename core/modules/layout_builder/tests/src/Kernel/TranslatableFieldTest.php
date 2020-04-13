<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Tests Layout Builder with a translatable layout field.
 *
 * @group layout_builder
 */
class TranslatableFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_discovery',
    'layout_builder',
    'entity_test',
    'field',
    'system',
    'user',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['key_value_expire']);
    $this->installEntitySchema('entity_test');

    // Create a translation.
    ConfigurableLanguage::createFromLangcode('es')->save();

    LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    FieldStorageConfig::loadByName('entity_test', OverridesSectionStorage::FIELD_NAME)
      ->setTranslatable(TRUE)
      ->save();
    FieldConfig::loadByName('entity_test', 'entity_test', OverridesSectionStorage::FIELD_NAME)
      ->setTranslatable(TRUE)
      ->save();
  }

  /**
   * Tests that sections on cleared when creating a new translation.
   */
  public function testSectionsClearedOnCreateTranslation() {
    $section_data = [
      new Section('layout_onecol', [], [
        'first-uuid' => new SectionComponent('first-uuid', 'content', ['id' => 'foo']),
      ]),
    ];
    $entity = EntityTest::create([OverridesSectionStorage::FIELD_NAME => $section_data]);
    $entity->save();
    $this->assertFalse($entity->get(OverridesSectionStorage::FIELD_NAME)->isEmpty());

    $entity = EntityTest::load($entity->id());
    /** @var \Drupal\entity_test\Entity\EntityTest $translation */
    $translation = $entity->addTranslation('es', $entity->toArray());

    // Per-language layouts are not supported.
    $this->assertTrue($translation->get(OverridesSectionStorage::FIELD_NAME)->isEmpty());
  }

}
