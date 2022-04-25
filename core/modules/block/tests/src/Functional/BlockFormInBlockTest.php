<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Component\Utility\Crypt;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests form in block caching.
 *
 * @group block
 */
class BlockFormInBlockTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'block_test', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable our test block.
    $this->drupalPlaceBlock('test_form_in_block');
  }

  /**
   * Tests to see if form in block's redirect isn't cached.
   */
  public function testCachePerPage() {
    $form_values = ['email' => 'test@example.com'];

    // Go to "test-page" and test if the block is enabled.
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your .com email address.');

    // Make sure that we're currently still on /test-page after submitting the
    // form.
    $this->submitForm($form_values, 'Submit');
    $this->assertSession()->addressEquals('test-page');
    $this->assertSession()->pageTextContains('Your email address is test@example.com');

    // Go to a different page and see if the block is enabled there as well.
    $this->drupalGet('test-render-title');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your .com email address.');

    // Make sure that submitting the form didn't redirect us to the first page
    // we submitted the form from after submitting the form from
    // /test-render-title.
    $this->submitForm($form_values, 'Submit');
    $this->assertSession()->addressEquals('test-render-title');
    $this->assertSession()->pageTextContains('Your email address is test@example.com');
  }

  /**
   * Tests the actual placeholders.
   */
  public function testPlaceholders() {
    $this->drupalGet('test-multiple-forms');

    $placeholder = 'form_action_' . Crypt::hashBase64('Drupal\Core\Form\FormBuilder::prepareForm');
    $this->assertSession()->pageTextContains('Form action: ' . $placeholder);
  }

}
