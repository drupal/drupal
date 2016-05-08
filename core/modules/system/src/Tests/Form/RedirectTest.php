<?php

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests form redirection functionality.
 *
 * @group Form
 */
class RedirectTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test', 'block');

  /**
   * Tests form redirection.
   */
  function testRedirect() {
    $path = 'form-test/redirect';
    $options = array('query' => array('foo' => 'bar'));
    $options['absolute'] = TRUE;

    // Test basic redirection.
    $edit = array(
      'redirection' => TRUE,
      'destination' => $this->randomMachineName(),
    );
    $this->drupalPostForm($path, $edit, t('Submit'));
    $this->assertUrl($edit['destination'], array(), 'Basic redirection works.');


    // Test without redirection.
    $edit = array(
      'redirection' => FALSE,
    );
    $this->drupalPostForm($path, $edit, t('Submit'));
    $this->assertUrl($path, array(), 'When redirect is set to FALSE, there should be no redirection.');

    // Test redirection with query parameters.
    $edit = array(
      'redirection' => TRUE,
      'destination' => $this->randomMachineName(),
    );
    $this->drupalPostForm($path, $edit, t('Submit'), $options);
    $this->assertUrl($edit['destination'], array(), 'Redirection with query parameters works.');

    // Test without redirection but with query parameters.
    $edit = array(
      'redirection' => FALSE,
    );
    $this->drupalPostForm($path, $edit, t('Submit'), $options);
    $this->assertUrl($path, $options, 'When redirect is set to FALSE, there should be no redirection, and the query parameters should be passed along.');

    // Test redirection back to the original path.
    $edit = array(
      'redirection' => TRUE,
      'destination' => '',
    );
    $this->drupalPostForm($path, $edit, t('Submit'));
    $this->assertUrl($path, array(), 'When using an empty redirection string, there should be no redirection.');

    // Test redirection back to the original path with query parameters.
    $edit = array(
      'redirection' => TRUE,
      'destination' => '',
    );
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
    $user = $this->drupalCreateUser(array('administer themes'));
    $this->drupalLogin($user);

    // Visit page 'foo' (404 page) and submit the form. Verify it ends up
    // at the right URL.
    $expected = \Drupal::url('form_test.route1', array(), array('query' => array('test1' => 'test2'), 'absolute' => TRUE));
    $this->drupalGet('foo');
    $this->assertResponse(404);
    $this->drupalPostForm(NULL, array(), t('Submit'));
    $this->assertResponse(200);
    $this->assertUrl($expected, [], 'Redirected to correct URL/query.');

    // Visit the block admin page (403 page) and submit the form. Verify it
    // ends up at the right URL.
    $this->drupalGet('admin/structure/block');
    $this->assertResponse(403);
    $this->drupalPostForm(NULL, array(), t('Submit'));
    $this->assertResponse(200);
    $this->assertUrl($expected, [], 'Redirected to correct URL/query.');
  }

}
