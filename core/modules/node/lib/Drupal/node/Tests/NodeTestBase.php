<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTestBase.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Sets up page and article content types.
 */
abstract class NodeTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'datetime');

  function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }

  /**
   * Asserts that node_access() correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected node access grants for the node
   *   and account, with each key as the name of an operation (e.g. 'view',
   *   'delete') and each value a Boolean indicating whether access to that
   *   operation should be granted.
   * @param \Drupal\node\Plugin\Core\Entity\Node $node
   *   The node object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the node
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  function assertNodeAccess(array $ops, $node, AccountInterface $account, $langcode = NULL) {
    foreach ($ops as $op => $result) {
      $msg = format_string(
        'node_access() returns @result with operation %op, language code %langcode.',
        array(
          '@result' => $result ? 'true' : 'false',
          '%op' => $op,
          '%langcode' => !empty($langcode) ? $langcode : 'empty'
        )
      );
      $this->assertEqual($result, node_access($op, $node, $account, $langcode), $msg);
    }
  }

}
