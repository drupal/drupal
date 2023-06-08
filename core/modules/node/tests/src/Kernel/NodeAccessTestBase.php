<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Defines a base class for node access kernel tests.
 */
abstract class NodeAccessTestBase extends KernelTestBase {

  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }
  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }
  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'datetime',
    'user',
    'system',
    'filter',
    'field',
    'text',
  ];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('filter');
    $this->installConfig('node');

    $this->accessHandler = \Drupal::entityTypeManager()->getAccessControlHandler('node');

    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
      ->set('permissions', [])
      ->save();

    // Create user 1 who has special permissions.
    $this->drupalCreateUser();

    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);
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
      $this->assertEquals($result, $this->accessHandler->access($node, $op, $account), $this->nodeAccessAssertMessage($op, $result, $node->language()
        ->getId()));
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
    $this->assertEquals($result, $this->accessHandler->createAccess($bundle, $account, [
      'langcode' => $langcode,
    ]), $this->nodeAccessAssertMessage('create', $result, $langcode));
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
