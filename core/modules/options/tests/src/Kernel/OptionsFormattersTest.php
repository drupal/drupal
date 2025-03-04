<?php

declare(strict_types=1);

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
   * Tests the formatters.
   */
  public function testFormatter(): void {
    $entity = EntityTest::create();
    $entity->{$this->fieldName}->value = 1;

    $items = $entity->get($this->fieldName);

    $build = $items->view();
    $this->assertEquals('list_default', $build['#formatter'], 'Ensure to fall back to the default formatter.');
    $this->assertEquals('One', $build[0]['#markup']);

    $build = $items->view(['type' => 'list_key']);
    $this->assertEquals('list_key', $build['#formatter'], 'The chosen formatter is used.');
    $this->assertEquals(1, (string) $build[0]['#markup']);
  }

}
