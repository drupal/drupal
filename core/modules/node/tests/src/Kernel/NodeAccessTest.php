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
 * Tests basic node_access functionality.
 *
 * @group node
 */
class NodeAccessTest extends KernelTestBase {

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
  public static $modules = [
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
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('filter');
    $this->installConfig('node');
    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('node');
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
   * Runs basic tests for node_access function.
   */
  public function testNodeAccess() {
    // Ensures user without 'access content' permission can do nothing.
    $web_user1 = $this->drupalCreateUser([
      'create page content',
      'edit any page content',
      'delete any page content',
    ]);
    $node1 = $this->drupalCreateNode(['type' => 'page']);
    $this->assertNodeCreateAccess($node1->bundle(), FALSE, $web_user1);
    $this->assertNodeAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $node1, $web_user1);

    // Ensures user with 'bypass node access' permission can do everything.
    $web_user2 = $this->drupalCreateUser(['bypass node access']);
    $node2 = $this->drupalCreateNode(['type' => 'page']);
    $this->assertNodeCreateAccess($node2->bundle(), TRUE, $web_user2);
    $this->assertNodeAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $node2, $web_user2);

    // User cannot 'view own unpublished content'.
    $web_user3 = $this->drupalCreateUser(['access content']);
    $node3 = $this->drupalCreateNode([
      'status' => 0,
      'uid' => $web_user3->id(),
    ]);
    $this->assertNodeAccess(['view' => FALSE], $node3, $web_user3);

    // User cannot create content without permission.
    $this->assertNodeCreateAccess($node3->bundle(), FALSE, $web_user3);

    // User can 'view own unpublished content', but another user cannot.
    $web_user4 = $this->drupalCreateUser([
      'access content',
      'view own unpublished content',
    ]);
    $web_user5 = $this->drupalCreateUser([
      'access content',
      'view own unpublished content',
    ]);
    $node4 = $this->drupalCreateNode([
      'status' => 0,
      'uid' => $web_user4->id(),
    ]);
    $this->assertNodeAccess([
      'view' => TRUE,
      'update' => FALSE,
    ], $node4, $web_user4);
    $this->assertNodeAccess(['view' => FALSE], $node4, $web_user5);

    // Tests the default access provided for a published node.
    $node5 = $this->drupalCreateNode();
    $this->assertNodeAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $node5, $web_user3);

    // Tests the "edit any BUNDLE" and "delete any BUNDLE" permissions.
    $web_user6 = $this->drupalCreateUser([
      'access content',
      'edit any page content',
      'delete any page content',
    ]);
    $node6 = $this->drupalCreateNode(['type' => 'page']);
    $this->assertNodeAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $node6, $web_user6);

    // Tests the "edit own BUNDLE" and "delete own BUNDLE" permission.
    $web_user7 = $this->drupalCreateUser([
      'access content',
      'edit own page content',
      'delete own page content',
    ]);
    // User should not be able to edit or delete nodes they do not own.
    $this->assertNodeAccess([
      'view' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $node6, $web_user7);

    // User should be able to edit or delete nodes they own.
    $node7 = $this->drupalCreateNode([
      'type' => 'page',
      'uid' => $web_user7->id(),
    ]);
    $this->assertNodeAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $node7, $web_user7);
  }

  /**
   * Test operations not supported by node grants.
   */
  public function testUnsupportedOperation() {
    $this->enableModules(['node_access_test_empty']);
    $web_user = $this->drupalCreateUser(['access content']);
    $node = $this->drupalCreateNode();
    $this->assertNodeAccess(['random_operation' => FALSE], $node, $web_user);
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
   */
  public function assertNodeCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
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
