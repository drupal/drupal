<?php

/**
 * @file
 * Definition of Drupal\php\Tests\PhpTestBase.
 */

namespace Drupal\php\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines a base PHP test case class.
 */
class PhpTestBase extends WebTestBase {
  protected $php_code_format;

  function setUp() {
    parent::setUp('php');

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create and login admin user.
    $admin_user = $this->drupalCreateUser(array('administer filters'));
    $this->drupalLogin($admin_user);

    // Verify that the PHP code text format was inserted.
    $php_format_id = 'php_code';
    $this->php_code_format = filter_format_load($php_format_id);
    $this->assertEqual($this->php_code_format->name, 'PHP code', t('PHP code text format was created.'));

    // Verify that the format has the PHP code filter enabled.
    $filters = filter_list_format($php_format_id);
    $this->assertTrue($filters['php_code']->status, t('PHP code filter is enabled.'));

    // Verify that the format exists on the administration page.
    $this->drupalGet('admin/config/content/formats');
    $this->assertText('PHP code', t('PHP code text format was created.'));

    // Verify that anonymous and authenticated user roles do not have access.
    $this->drupalGet('admin/config/content/formats/' . $php_format_id);
    $this->assertFieldByName('roles[' . DRUPAL_ANONYMOUS_RID . ']', FALSE, t('Anonymous users do not have access to PHP code format.'));
    $this->assertFieldByName('roles[' . DRUPAL_AUTHENTICATED_RID . ']', FALSE, t('Authenticated users do not have access to PHP code format.'));
  }

  /**
   * Creates a test node with PHP code in the body.
   *
   * @return stdObject Node object.
   */
  function createNodeWithCode() {
    return $this->drupalCreateNode(array('body' => array(LANGUAGE_NOT_SPECIFIED => array(array('value' => '<?php print "SimpleTest PHP was executed!"; ?>')))));
  }
}
