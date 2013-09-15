<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\RedirectTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests form redirection.
 */
class RedirectTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  public static function getInfo() {
    return array(
      'name' => 'Form redirecting',
      'description' => 'Tests functionality of drupal_redirect_form().',
      'group' => 'Form API',
    );
  }

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
      'destination' => $this->randomName(),
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
      'destination' => $this->randomName(),
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

}
