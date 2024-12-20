<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * @covers \Drupal\Core\Recipe\RecipeInputFormTrait
 * @group system
 */
class RecipeFormInputTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests collecting recipe input via a form.
   */
  public function testRecipeInputViaForm(): void {
    $this->drupalGet('/form-test/recipe-input');

    $assert_session = $this->assertSession();
    // There should only be two nested input elements on the page: the two
    // defined by the input_test recipe.
    $assert_session->elementsCount('css', 'input[name*="["]', 2);
    // The default value and description should be visible.
    $assert_session->fieldValueEquals('input_test[owner]', 'Dries Buytaert');
    $assert_session->pageTextContains('The name of the site owner.');
    $this->assertSame('checkbox', $assert_session->fieldExists('Allow mischief')->getAttribute('type'));
    // All recipe inputs are required, except for checkboxes, for which that
    // behavior makes no sense.
    $this->submitForm(['input_test[owner]' => ''], 'Apply recipe');
    $assert_session->statusCodeEquals(200);
    $assert_session->statusMessageContains("Site owner's name field is required.", 'error');
    $assert_session->statusMessageNotContains('Allow mischief field is required.', 'error');
    // All inputs should be validated with their own constraints.
    $this->submitForm(['input_test[owner]' => 'Hacker Joe'], 'Apply recipe');
    $assert_session->statusMessageContains("I don't think you should be owning sites.", 'error');
    // The correct element should be flagged as invalid.
    $assert_session->elementAttributeExists('named', ['field', 'input_test[owner]'], 'aria-invalid');
    // Submit the form with a valid value and apply the recipe, to prove that
    // it was passed through correctly.
    $this->submitForm(['input_test[owner]' => 'Legitimate Human'], 'Apply recipe');
    $this->assertSame("Legitimate Human's Turf", $this->config('system.site')->get('name'));
  }

}
