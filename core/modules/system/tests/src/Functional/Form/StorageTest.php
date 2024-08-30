<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests a multistep form using form storage.
 *
 * The tested form puts data into the storage during the initial form
 * construction. These tests verify that there are no duplicate form
 * constructions, with and without manual form caching activated. Furthermore
 * when a validation error occurs, it makes sure that changed form element
 * values are not lost due to a wrong form rebuild.
 *
 * @group Form
 */
class StorageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser());
  }

  /**
   * Tests using the form in a usual way.
   */
  public function testForm(): void {
    $this->drupalGet('form_test/form-storage');

    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Form constructions: 1');

    $edit = ['title' => 'new', 'value' => 'value_is_set'];

    // Use form rebuilding triggered by a submit button.
    $this->submitForm($edit, 'Continue submit');
    $assert_session->pageTextContains('Form constructions: 2');
    $assert_session->pageTextContains('Form constructions: 3');

    // Reset the form to the values of the storage, using a form rebuild
    // triggered by button of type button.
    $this->submitForm(['title' => 'changed'], 'Reset');
    $assert_session->fieldValueEquals('title', 'new');
    // After rebuilding, the form has been cached.
    $assert_session->pageTextContains('Form constructions: 4');

    $this->submitForm($edit, 'Save');
    $assert_session->pageTextContains('Form constructions: 4');
    // Verify that the form storage has stored the values.
    $assert_session->pageTextContains('Title: new');
  }

  /**
   * Tests using the form after calling $form_state->setCached().
   */
  public function testFormCached(): void {
    $this->drupalGet('form_test/form-storage', ['query' => ['cache' => 1]]);
    $this->assertSession()->pageTextContains('Form constructions: 1');

    $edit = ['title' => 'new', 'value' => 'value_is_set'];

    // Use form rebuilding triggered by a submit button.
    $this->submitForm($edit, 'Continue submit');
    // The first one is for the building of the form.
    $this->assertSession()->pageTextContains('Form constructions: 2');
    // The second one is for the rebuilding of the form.
    $this->assertSession()->pageTextContains('Form constructions: 3');

    // Reset the form to the values of the storage, using a form rebuild
    // triggered by button of type button.
    $this->submitForm(['title' => 'changed'], 'Reset');
    $this->assertSession()->fieldValueEquals('title', 'new');
    $this->assertSession()->pageTextContains('Form constructions: 4');

    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Form constructions: 4');
    // Verify that the form storage has stored the values.
    $this->assertSession()->pageTextContains('Title: new');
  }

  /**
   * Tests validation when form storage is used.
   */
  public function testValidation(): void {
    $this->drupalGet('form_test/form-storage');
    $this->submitForm([
      'title' => '',
      'value' => 'value_is_set',
    ], 'Continue submit');
    // Ensure that the input values have been kept.
    $this->assertSession()->responseMatches('/value_is_set/');
  }

  /**
   * Tests updating cached form storage during form validation.
   *
   * If form caching is enabled and a form stores data in the form storage, then
   * the form storage also has to be updated in case of a validation error in
   * the form. This test re-uses the existing form for multi-step tests, but
   * triggers a special #element_validate handler to update the form storage
   * during form validation, while another, required element in the form
   * triggers a form validation error.
   */
  public function testCachedFormStorageValidation(): void {
    // Request the form with 'cache' query parameter to enable form caching.
    $this->drupalGet('form_test/form-storage', ['query' => ['cache' => 1]]);

    // Skip step 1 of the multi-step form, since the first step copies over
    // 'title' into form storage, but we want to verify that changes in the form
    // storage are updated in the cache during form validation.
    $edit = ['title' => 'foo'];
    $this->submitForm($edit, 'Continue submit');

    // In step 2, trigger a validation error for the required 'title' field, and
    // post the special 'change_title' value for the 'value' field, which
    // conditionally invokes the #element_validate handler to update the form
    // storage.
    $edit = ['title' => '', 'value' => 'change_title'];
    $this->submitForm($edit, 'Save');

    // At this point, the form storage should contain updated values, but we do
    // not see them, because the form has not been rebuilt yet due to the
    // validation error. Post again and verify that the rebuilt form contains
    // the values of the updated form storage.
    $this->submitForm(['title' => 'foo', 'value' => 'bar'], 'Save');
    $this->assertSession()->pageTextContains("The thing has been changed.");
  }

}
