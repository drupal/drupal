<?php

namespace Drupal\Tests\options\Kernel;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the Options field type formatters.
 *
 * @group options
 * @see \Drupal\options\Plugin\Field\FieldFormatter\OptionsDefaultFormatter
 * @see \Drupal\options\Plugin\Field\FieldFormatter\OptionsKeyFormatter
 */
class OptionsFormattersTest extends OptionsFieldUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the formatters.
   */
  public function testFormatter() {
    $entity = EntityTest::create();
    $entity->{$this->fieldName}->value = 1;

    $items = $entity->get($this->fieldName);

    $build = $items->view();
    $this->assertEqual($build['#formatter'], 'list_default', 'Ensure to fall back to the default formatter.');
    $this->assertEqual($build[0]['#markup'], 'One');

    $build = $items->view(array('type' => 'list_key'));
    $this->assertEqual($build['#formatter'], 'list_key', 'The chosen formatter is used.');
    $this->assertEqual((string) $build[0]['#markup'], 1);
  }

}
