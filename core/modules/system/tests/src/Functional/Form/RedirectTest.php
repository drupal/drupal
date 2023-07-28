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
    $this->drupalGet($path);
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->addressEquals($edit['destination']);

    // Test without redirection.
    $edit = [
      'redirection' => FALSE,
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->addressEquals($path);

    // Test redirection with query parameters.
    $edit = [
      'redirection' => TRUE,
      'destination' => $this->randomMachineName(),
    ];
    $this->drupalGet($path, $options);
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->addressEquals($edit['destination']);

    // Test without redirection but with query parameters.
    $edit = [
      'redirection' => FALSE,
    ];
    $this->drupalGet($path, $options);
    $this->submitForm($edit, 'Submit');
    // When redirect is set to FALSE, there should be no redirection, and the
    // query parameters should be passed along.
    $this->assertSession()->addressEquals($path . '?foo=bar');

    // Test redirection back to the original path.
    $edit = [
      'redirection' => TRUE,
      'destination' => '',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->addressEquals($path);

    // Test redirection back to the original path with query parameters.
    $this->drupalGet($path, $options);
    $this->submitForm($edit, 'Submit');
    // When using an empty redirection string, there should be no redirection,
    // and the query parameters should be passed along.
    $this->assertSession()->addressEquals($path . '?foo=bar');

    // Test basic redirection, ignoring the 'destination' query parameter.
    $options['query']['destination'] = $this->randomMachineName();
    $edit = [
      'redirection' => TRUE,
      'destination' => $this->randomMachineName(),
      'ignore_destination' => TRUE,
    ];
    $this->drupalGet($path, $options);
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->addressEquals($edit['destination']);

    // Test redirection with query param, ignoring the 'destination' query
    // parameter.
    $options['query']['destination'] = $this->randomMachineName();
    $edit = [
      'redirection' => TRUE,
      'destination' => $this->randomMachineName() . '?foo=bar',
      'ignore_destination' => TRUE,
    ];
    $this->drupalGet($path, $options);
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->addressEquals($edit['destination']);
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
    $this->submitForm([], 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($expected);

    // Visit the block admin page (403 page) and submit the form. Verify it
    // ends up at the right URL.
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->statusCodeEquals(403);
    $this->submitForm([], 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($expected);
  }

}
