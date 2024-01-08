<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the proper removal of submitted form values.
 *
 * @see \Drupal\Core\Form\FormState::cleanValues()
 *
 * @group Form
 */
class StateValuesCleanTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests \Drupal\Core\Form\FormState::cleanValues().
   */
  public function testFormStateValuesClean() {
    $this->drupalGet('form_test/form-state-values-clean');
    $this->submitForm([], 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());

    // Setup the expected result.
    $result = [
      'beer' => 1000,
      'baz' => ['beer' => 2000],
    ];

    // Verify that all internal Form API elements were removed.
    $this->assertFalse(isset($values['form_id']), 'form_id was removed.');
    $this->assertFalse(isset($values['form_token']), 'form_token was removed.');
    $this->assertFalse(isset($values['form_build_id']), 'form_build_id was removed.');
    $this->assertFalse(isset($values['op']), 'op was removed.');

    // Verify that all buttons were removed.
    $this->assertFalse(isset($values['foo']), 'foo was removed.');
    $this->assertFalse(isset($values['bar']), 'bar was removed.');
    $this->assertFalse(isset($values['baz']['foo']), 'foo was removed.');
    $this->assertFalse(isset($values['baz']['baz']), 'baz was removed.');

    // Verify values manually added for cleaning were removed.
    $this->assertFalse(isset($values['wine']), 'wine was removed.');

    // Verify that nested form value still exists.
    $this->assertTrue(isset($values['baz']['beer']), 'Nested form value still exists.');

    // Verify that actual form values equal resulting form values.
    $this->assertEquals($result, $values, 'Expected form values equal actual form values.');
  }

}
