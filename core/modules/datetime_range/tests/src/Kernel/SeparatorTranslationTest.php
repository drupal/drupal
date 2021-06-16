<?php

namespace Drupal\Tests\datetime_range\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Language\Language;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Test to ensure the datetime range separator is translatable.
 *
 * @group datetime
 */
class SeparatorTranslationTest extends KernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'datetime_range',
    'entity_test',
    'field',
    'language',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(['system']);
    $this->installSchema('system', ['sequences']);

    // Add a datetime range field.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => mb_strtolower($this->randomMachineName()),
      'entity_type' => 'entity_test',
      'type' => 'daterange',
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATE],
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $this->field->save();

    $display_options = [
      'type' => 'daterange_default',
      'label' => 'hidden',
      'settings' => [
        'format_type' => 'fallback',
        'separator' => 'UNTRANSLATED',
      ],
    ];
    EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($this->fieldStorage->getName(), $display_options)
      ->save();
  }

  /**
   * Tests the translation of the range separator.
   */
  public function testSeparatorTranslation() {
    // Create an entity.
    $entity = EntityTest::create([
      'name' => $this->randomString(),
      $this->fieldStorage->getName() => [
        'value' => '2016-09-20',
        'end_value' => '2016-09-21',
      ],
    ]);

    // Verify the untranslated separator.
    $display = EntityViewDisplay::collectRenderDisplay($entity, 'default');
    $build = $display->build($entity);
    $output = $this->container->get('renderer')->renderRoot($build);
    $this->assertStringContainsString('UNTRANSLATED', (string) $output);

    // Translate the separator.
    ConfigurableLanguage::createFromLangcode('nl')->save();
    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');
    $language_manager->getLanguageConfigOverride('nl', 'core.entity_view_display.entity_test.entity_test.default')
      ->set('content.' . $this->fieldStorage->getName() . '.settings.separator', 'NL_TRANSLATED!')
      ->save();

    $this->container->get('language.config_factory_override')
      ->setLanguage(new Language(['id' => 'nl']));
    $this->container->get('cache_tags.invalidator')->invalidateTags($entity->getCacheTags());
    $display = EntityViewDisplay::collectRenderDisplay($entity, 'default');
    $build = $display->build($entity);
    $output = $this->container->get('renderer')->renderRoot($build);
    $this->assertStringContainsString('NL_TRANSLATED!', (string) $output);
  }

}
