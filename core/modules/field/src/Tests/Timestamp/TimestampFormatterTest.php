<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Timestamp\TimestampFormatterTest.
 */

namespace Drupal\field\Tests\Timestamp;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the timestamp formatters.
 *
 * @group field
 */
class TimestampFormatterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'field', 'text', 'entity_test', 'user'];

  /**
   * @var string
   */
  protected $entityType;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installEntitySchema('entity_test');

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = Unicode::strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'timestamp',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

    $this->display = entity_get_display($this->entityType, $this->bundle, 'default')
      ->setComponent($this->fieldName, [
        'type' => 'boolean',
        'settings' => [],
      ]);
    $this->display->save();
  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   *
   * @return string
   *   The rendered entity fields.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity, EntityViewDisplayInterface $display) {
    $content = $display->build($entity);
    $content = $this->render($content);
    return $content;
  }

  /**
   * Tests TimestampFormatter.
   */
  protected function testTimestampFormatter() {
    $data = [];

    // Test standard formats.
    $date_formats = array_keys(\Drupal::entityManager()->getStorage('date_format')->loadMultiple());

    foreach ($date_formats as $date_format) {
      $data[] = ['date_format' => $date_format, 'custom_date_format' => '', 'timezone' => ''];
    }

    $data[] = ['date_format' => 'custom', 'custom_date_format' => 'r', 'timezone' => ''];
    $data[] = ['date_format' => 'custom', 'custom_date_format' => 'e', 'timezone' => 'Asia/Tokyo'];

    foreach ($data as $settings) {
      list($date_format, $custom_date_format, $timezone) = array_values($settings);
      if (empty($timezone)) {
        $timezone = NULL;
      }

      $value = REQUEST_TIME - 87654321;
      $expected = \Drupal::service('date.formatter')->format($value, $date_format, $custom_date_format, $timezone);

      $component = $this->display->getComponent($this->fieldName);
      $component['type'] = 'timestamp';
      $component['settings'] = $settings;
      $this->display->setComponent($this->fieldName, $component);

      $entity = EntityTest::create([]);
      $entity->{$this->fieldName}->value = $value;

      $this->renderEntityFields($entity, $this->display);
      $this->assertRaw($expected);
    }
  }

  /**
   * Tests TimestampAgoFormatter.
   */
  protected function testTimestampAgoFormatter() {
    $data = [];

    foreach (array(1,2,3,4,5,6) as $granularity) {
      $data[] = [
        'future_format' => '@interval hence',
        'past_format' => '@interval ago',
        'granularity' => $granularity,
      ];
    }

    foreach ($data as $settings) {
      $future_format = $settings['future_format'];
      $past_format = $settings['past_format'];
      $granularity = $settings['granularity'];
      $request_time = \Drupal::requestStack()->getCurrentRequest()->server->get('REQUEST_TIME');

      // Test a timestamp in the past
      $value = $request_time - 87654321;
      $expected = SafeMarkup::format($past_format, ['@interval' => \Drupal::service('date.formatter')->formatTimeDiffSince($value, ['granularity' => $granularity])]);

      $component = $this->display->getComponent($this->fieldName);
      $component['type'] = 'timestamp_ago';
      $component['settings'] = $settings;
      $this->display->setComponent($this->fieldName, $component);

      $entity = EntityTest::create([]);
      $entity->{$this->fieldName}->value = $value;

      $this->renderEntityFields($entity, $this->display);
      $this->assertRaw($expected);

      // Test a timestamp in the future
      $value = $request_time + 87654321;
      $expected = SafeMarkup::format($future_format, ['@interval' => \Drupal::service('date.formatter')->formatTimeDiffUntil($value, ['granularity' => $granularity])]);

      $component = $this->display->getComponent($this->fieldName);
      $component['type'] = 'timestamp_ago';
      $component['settings'] = $settings;
      $this->display->setComponent($this->fieldName, $component);

      $entity = EntityTest::create([]);
      $entity->{$this->fieldName}->value = $value;

      $this->renderEntityFields($entity, $this->display);
      $this->assertRaw($expected);
    }
  }

}
