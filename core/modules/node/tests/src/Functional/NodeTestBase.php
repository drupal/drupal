<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Sets up page and article content types.
 */
abstract class NodeTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'datetime'];

  /**
   * The node access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
    $this->accessHandler = \Drupal::entityTypeManager()->getAccessControlHandler('node');
  }

  /**
   * Asserts that node access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected node access grants for the node
   *   and account, with each key as the name of an operation (e.g. 'view',
   *   'delete') and each value a Boolean indicating whether access to that
   *   operation should be granted.
   * @param \Drupal\node\NodeInterface $node
   *   The node object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   *
   * @internal
   */
  public function assertNodeAccess(array $ops, NodeInterface $node, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEquals($this->accessHandler->access($node, $op, $account), $result, $this->nodeAccessAssertMessage($op, $result, $node->language()->getId()));
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
   *
   * @internal
   */
  public function assertNodeCreateAccess(string $bundle, bool $result, AccountInterface $account, ?string $langcode = NULL) {
    $this->assertEquals($this->accessHandler->createAccess($bundle, $account, ['langcode' => $langcode]), $result, $this->nodeAccessAssertMessage('create', $result, $langcode));
  }

  /**
   * Constructs an assert message to display which node access was tested.
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
   *   An assert message string which contains information in plain English
   *   about the node access permission test that was performed.
   */
  public function nodeAccessAssertMessage($operation, $result, $langcode = NULL) {
    return new FormattableMarkup(
      'Node access returns @result with operation %op, language code %langcode.',
      [
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty',
      ]
    );
  }

}
