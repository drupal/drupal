<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests form redirection functionality.
 *
 * @group Form
 */
class RedirectTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests form redirection.
   */
  public function testRedirect() {
    $path = 'form-test/redirect';
    $options = ['query' => ['foo' => 'bar']];
    $options['absolute'] = TRUE;

    // Test basic redirection.
    $edit = [
      'redirection' => TRUE,
      'destination' => $this->randomMachineName(),
    ];
    $this->drupalPostForm($path, $edit, t('Submit'));
    $this->assertUrl($edit['destination'], [], 'Basic redirection works.');

    // Test without redirection.
    $edit = [
      'redirection' => FALSE,
    ];
    $this->drupalPostForm($path, $edit, t('Submit'));
    $this->assertUrl($path, [], 'When redirect is set to FALSE, there should be no redirection.');

    // Test redirection with query parameters.
    $edit = [
      'redirection' => TRUE,
      'destination' => $this->randomMachineName(),
    ];
    $this->drupalPostForm($path, $edit, t('Submit'), $options);
    $this->assertUrl($edit['destination'], [], 'Redirection with query parameters works.');

    // Test without redirection but with query parameters.
    $edit = [
      'redirection' => FALSE,
    ];
    $this->drupalPostForm($path, $edit, t('Submit'), $options);
    $this->assertUrl($path, $options, 'When redirect is set to FALSE, there should be no redirection, and the query parameters should be passed along.');

    // Test redirection back to the original path.
    $edit = [
      'redirection' => TRUE,
      'destination' => '',
    ];
    $this->drupalPostForm($path, $edit, t('Submit'));
    $this->assertUrl($path, [], 'When using an empty redirection string, there should be no redirection.');

    // Test redirection back to the original path with query parameters.
    $edit = [
      'redirection' => TRUE,
      'destination' => '',
    ];
    $this->drupalPostForm($path, $edit, t('Submit'), $options);
    $this->assertUrl($path, $options, 'When using an empty redirection string, there should be no redirection, and the query parameters should be passed along.');
  }

  /**
   * Tests form redirection from 404/403 pages with the Block form.
   */
  public function testRedirectFromErrorPages() {
    // Make sure the block containing the redirect form is placed.
    $this->drupalPlaceBlock('redirect_form_block');

    // Create a user that does not have permission to administer blocks.
    $user = $this->drupalCreateUser(['administer themes']);
    $this->drupalLogin($user);

    // Visit page 'foo' (404 page) and submit the form. Verify it ends up
    // at the right URL.
    $expected = Url::fromRoute('form_test.route1', [], ['query' => ['test1' => 'test2'], 'absolute' => TRUE])->toString();
    $this->drupalGet('foo');
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalPostForm(NULL, [], t('Submit'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertUrl($expected, [], 'Redirected to correct URL/query.');

    // Visit the block admin page (403 page) and submit the form. Verify it
    // ends up at the right URL.
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalPostForm(NULL, [], t('Submit'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertUrl($expected, [], 'Redirected to correct URL/query.');
  }

}
