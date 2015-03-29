<?php

/**
 * @file
 * Contains \Drupal\field\Tests\String\RawStringFormatterTest.
 */

namespace Drupal\field\Tests\String;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the raw string formatter
 *
 * @group field
 */
class RawStringFormatterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field', 'text', 'entity_test', 'system', 'filter', 'user');

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

    // Configure the theme system.
    $this->installConfig(array('system', 'field'));
    $this->installSchema('system', 'router');
    \Drupal::service('router.builder')->rebuild();
    $this->installEntitySchema('entity_test');

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = Unicode::strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'string_long',
    ));
    $field_storage->save();

    $instance = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ));
    $instance->save();

    $this->display = entity_get_display($this->entityType, $this->bundle, 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'string',
        'settings' => array(),
      ));
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
   * Tests string formatter output.
   */
  public function testStringFormatter() {
    $value = $this->randomString();
    $value .= "\n\n<strong>" . $this->randomString() . '</strong>';
    $value .= "\n\n" . $this->randomString();

    $entity = EntityTest::create(array());
    $entity->{$this->fieldName}->value = $value;

    // Verify that all HTML is escaped and newlines are retained.
    $this->renderEntityFields($entity, $this->display);
    $this->assertNoRaw($value);
    $this->assertRaw(nl2br(SafeMarkup::checkPlain($value)));

    // Verify the cache tags.
    $build = $entity->{$this->fieldName}->view();
    $this->assertTrue(!isset($build[0]['#cache']), format_string('The string formatter has no cache tags.'));
  }

}
