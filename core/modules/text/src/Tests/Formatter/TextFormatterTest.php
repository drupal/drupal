<?php

/**
 * @file
 * Contains \Drupal\text\Tests\Formatter\TextFormatterTest.
 */

namespace Drupal\text\Tests\Formatter;

use Drupal\filter\Entity\FilterFormat;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests the text formatters functionality.
 *
 * @group text
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
  protected function setUp() {
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

    entity_create('field_storage_config', array(
      'name' => 'formatted_text',
      'entity_type' => $this->entityType,
      'type' => 'text',
      'settings' => array(),
    ))->save();
    entity_create('field_config', array(
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'formatted_text',
      'label' => 'Filtered text',
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
    $entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $entity->formatted_text = array(
      'value' => 'Hello, world!',
      'format' => 'my_text_format',
    );
    $entity->save();

    foreach ($formatters as $formatter) {
      // Verify the text field formatter's render array.
      $build = $entity->get('formatted_text')->view(array('type' => $formatter));
      drupal_render($build[0]);
      $this->assertEqual($build[0]['#markup'], "<p>Hello, world!</p>\n");
      $this->assertEqual($build[0]['#cache']['tags'], FilterFormat::load('my_text_format')->getCacheTag(), format_string('The @formatter formatter has the expected cache tags when formatting a formatted text field.', array('@formatter' => $formatter)));
    }
  }

}
