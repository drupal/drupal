<?php

declare(strict_types=1);

namespace Drupal\Tests\options\Kernel;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\entity_test\Entity\EntityTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests OptionsWidgetBase.
 */
#[CoversClass(OptionsWidgetBase::class)]
#[Group('options')]
#[RunTestsInSeparateProcesses]
class OptionsWidgetBaseTest extends OptionsFieldUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['options', 'options_test'];

  /**
   * Tests that getSelectedOptions() skips NULL item values.
   */
  public function testGetSelectedOptionsWithNullValue(): void {
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($this->fieldName, ['type' => 'options_select_no_multiple'])
      ->save();

    // An entity with no value triggers formMultipleElements() which appends
    // an item with value = NULL before calling getSelectedOptions().
    $entity = EntityTest::create();
    $form = \Drupal::service('entity.form_builder')->getForm($entity);
    $this->assertSame([], $form[$this->fieldName]['widget'][0]['#default_value']);
  }

}
