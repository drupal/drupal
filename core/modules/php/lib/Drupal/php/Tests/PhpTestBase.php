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
abstract class PhpTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('php');

  protected $php_code_format;

  function setUp() {
    parent::setUp();

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create and login admin user.
    $admin_user = $this->drupalCreateUser(array('administer filters'));
    $this->drupalLogin($admin_user);

    // Verify that the PHP code text format was inserted.
    $php_format_id = 'php_code';
    $this->php_code_format = entity_load('filter_format', $php_format_id);
    $this->assertEqual($this->php_code_format->name, 'PHP code', 'PHP code text format was created.');

    // Verify that the format has the PHP code filter enabled.
    $filters = $this->php_code_format->filters();
    $this->assertTrue($filters->get('php_code')->status, 'PHP code filter is enabled.');

    // Verify that the format exists on the administration page.
    $this->drupalGet('admin/config/content/formats');
    $this->assertText('PHP code', 'PHP code text format was created.');

    // Verify that anonymous and authenticated user roles do not have access.
    $this->drupalGet('admin/config/content/formats/manage/' . $php_format_id);
    $this->assertFieldByName('roles[' . DRUPAL_ANONYMOUS_RID . ']', FALSE, 'Anonymous users do not have access to PHP code format.');
    $this->assertFieldByName('roles[' . DRUPAL_AUTHENTICATED_RID . ']', FALSE, 'Authenticated users do not have access to PHP code format.');
  }

  /**
   * Creates a test node with PHP code in the body.
   *
   * @return stdObject Node object.
   */
  function createNodeWithCode() {
    return $this->drupalCreateNode(array('body' => array(array('value' => '<?php print "SimpleTest PHP was executed!"; ?>'))));
  }
}
