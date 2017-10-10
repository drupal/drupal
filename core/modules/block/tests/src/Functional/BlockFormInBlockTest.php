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
  public static $modules = ['block', 'block_test', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable our test block.
    $this->drupalPlaceBlock('test_form_in_block');
  }

  /**
   * Test to see if form in block's redirect isn't cached.
   */
  public function testCachePerPage() {
    $form_values = ['email' => 'test@example.com'];

    // Go to "test-page" and test if the block is enabled.
    $this->drupalGet('test-page');
    $this->assertResponse(200);
    $this->assertText('Your .com email address.', 'form found');

    // Make sure that we're currently still on /test-page after submitting the
    // form.
    $this->drupalPostForm(NULL, $form_values, t('Submit'));
    $this->assertUrl('test-page');
    $this->assertText(t('Your email address is @email', ['@email' => 'test@example.com']));

    // Go to a different page and see if the block is enabled there as well.
    $this->drupalGet('test-render-title');
    $this->assertResponse(200);
    $this->assertText('Your .com email address.', 'form found');

    // Make sure that submitting the form didn't redirect us to the first page
    // we submitted the form from after submitting the form from
    // /test-render-title.
    $this->drupalPostForm(NULL, $form_values, t('Submit'));
    $this->assertUrl('test-render-title');
    $this->assertText(t('Your email address is @email', ['@email' => 'test@example.com']));
  }

  /**
   * Test the actual placeholders
   */
  public function testPlaceholders() {
    $this->drupalGet('test-multiple-forms');

    $placeholder = 'form_action_' . Crypt::hashBase64('Drupal\Core\Form\FormBuilder::prepareForm');
    $this->assertText('Form action: ' . $placeholder, 'placeholder found.');
  }

}
