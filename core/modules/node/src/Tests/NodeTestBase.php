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

  /**
   * The node access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array(
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');
  }

  /**
   * Asserts that node access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected node access grants for the node
   *   and account, with each key as the name of an operation (e.g. 'view',
   *   'delete') and each value a Boolean indicating whether access to that
   *   operation should be granted.
   * @param \Drupal\node\Entity\Node $node
   *   The node object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the node
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  function assertNodeAccess(array $ops, $node, AccountInterface $account, $langcode = NULL) {
    foreach ($ops as $op => $result) {
      if (empty($langcode)) {
        $langcode = $node->prepareLangcode();
      }
      $this->assertEqual($result, $this->accessHandler->access($node, $op, $langcode, $account), $this->nodeAccessAssertMessage($op, $result, $langcode));
    }
  }

  /**
   * Asserts that node create access correctly grants or denies access.
   *
   * @param string $bundle
   *   The node bundle to check access to.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the node
   *   to check. If NULL, the untranslated (fallback) access is checked.
   */
  function assertNodeCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
    $this->assertEqual($result, $this->accessHandler->createAccess($bundle, $account, array(
      'langcode' => $langcode,
    )), $this->nodeAccessAssertMessage('create', $result, $langcode));
  }

  /**
   * Constructs an assert message for checking node access.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the node
   *   to check. If NULL, the untranslated (fallback) access is checked.
   *
   * @return string
   */
  function nodeAccessAssertMessage($operation, $result, $langcode = NULL) {
    return format_string(
      'Node access returns @result with operation %op, language code %langcode.',
      array(
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty'
      )
    );
  }

}
