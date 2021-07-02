<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests proper removal of submitted form values using
 * \Drupal\Core\Form\FormState::cleanValues().
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
    $this->assertArrayNotHasKey('form_id', $values);
    $this->assertArrayNotHasKey('form_token', $values);
    $this->assertArrayNotHasKey('form_build_id', $values);
    $this->assertArrayNotHasKey('op', $values);

    // Verify that all buttons were removed.
    $this->assertArrayNotHasKey('foo', $values);
    $this->assertArrayNotHasKey('bar', $values);
    $this->assertArrayNotHasKey('foo', $values['baz'], new FormattableMarkup('%element was removed.', ['%element' => 'foo']));
    $this->assertArrayNotHasKey('baz', $values['baz'], new FormattableMarkup('%element was removed.', ['%element' => 'baz']));

    // Verify values manually added for cleaning were removed.
    $this->assertArrayNotHasKey('wine', $values);

    // Verify that nested form value still exists.
    $this->assertArrayHasKey('beer', $values['baz']);
    $this->assertNotNull($values['baz']['beer'], 'Nested form value still exists.');

    // Verify that actual form values equal resulting form values.
    $this->assertEquals($result, $values, 'Expected form values equal actual form values.');
  }

}
