<?php

/**
 * @file
 * Contains \Drupal\text\Tests\Formatter\TextFormatterTest.
 */

namespace Drupal\text\Tests\Formatter;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests Text formatters.
 */
class TextFormatterTest extends EntityUnitTestBase {

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('text');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Text formatters',
      'description' => 'Tests the text formatters functionality.',
      'group' => 'Text ',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    entity_create('filter_format', array(
      'format' => 'my_text_format',
      'name' => 'My text format',
      'filters' => array(
        'filter_autop' => array(
          'module' => 'filter',
          'status' => TRUE,
        ),
      ),
    ))->save();

    // Set up two fields: one with text processing enabled, the other disabled.
    entity_create('field_config', array(
      'name' => 'processed_text',
      'entity_type' => $this->entityType,
      'type' => 'text',
      'settings' => array(),
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'processed_text',
      'label' => 'Processed text',
      'settings' => array(
        'text_processing' => TRUE,
      ),
    ))->save();
    entity_create('field_config', array(
      'name' => 'unprocessed_text',
      'entity_type' => $this->entityType,
      'type' => 'text',
      'settings' => array(),
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'unprocessed_text',
      'label' => 'Unprocessed text',
      'settings' => array(
        'text_processing' => FALSE,
      ),
    ))->save();
  }

  /**
   * Tests all text field formatters.
   */
  public function testFormatters() {
    $formatters = array(
      'text_default',
      'text_trimmed',
      'text_summary_or_trimmed',
    );

    // Create the entity to be referenced.
    $entity = entity_create($this->entityType, array('name' => $this->randomName()));
    $entity->processed_text = array(
      'value' => 'Hello, world!',
      'format' => 'my_text_format',
    );
    $entity->unprocessed_text = array(
      'value' => 'Hello, world!',
    );
    $entity->save();

    foreach ($formatters as $formatter) {
      // Verify the processed text field formatter's render array.
      $build = $entity->get('processed_text')->view(array('type' => $formatter));
      $this->assertEqual($build[0]['#markup'], "<p>Hello, world!</p>\n");
      $expected_cache_tags = array(
        'filter_format' => array('my_text_format' => 'my_text_format'),
      );
      $this->assertEqual($build[0]['#cache']['tags'], $expected_cache_tags, format_string('The @formatter formatter has the expected cache tags when formatting a processed text field.', array('@formatter' => $formatter)));

      // Verify the unprocessed text field formatter's render array.
      $build = $entity->get('unprocessed_text')->view(array('type' => $formatter));
      debug($build[0]);
      $this->assertEqual($build[0]['#markup'], 'Hello, world!');
      $this->assertTrue(!isset($build[0]['#cache']), format_string('The @formatter formatter has the expected cache tags when formatting an unprocessed text field.', array('@formatter' => $formatter)));
    }
  }

}
