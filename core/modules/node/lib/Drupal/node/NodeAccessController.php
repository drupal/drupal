<?php

/**
 * @file
 * Contains \Drupal\node\NodeAccessController.
 */

namespace Drupal\node;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access controller for the node entity type.
 */
class NodeAccessController extends EntityAccessController implements NodeAccessControllerInterface, EntityControllerInterface {

  /**
   * The node grant storage.
   *
   * @var \Drupal\node\NodeGrantStorageControllerInterface
   */
  protected $grantStorage;

  /**
   * Constructs a NodeAccessController object.
   *
   * @param string $entity_type
   *   The entity type of the access controller instance.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\node\NodeGrantDatabaseStorageInterface $grant_storage
   *   The node grant storage.
   */
  public function __construct($entity_type, array $entity_info, NodeGrantDatabaseStorageInterface $grant_storage) {
    parent::__construct($entity_type, $entity_info);
    $this->grantStorage = $grant_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('node.grant_storage')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = Language::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    if (user_access('bypass node access', $account)) {
      return TRUE;
    }
    if (!user_access('access content', $account)) {
      return FALSE;
    }
    return parent::access($entity, $operation, $langcode, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array()) {
    $account = $this->prepareUser($account);

    if (user_access('bypass node access', $account)) {
      return TRUE;
    }
    if (!user_access('access content', $account)) {
      return FALSE;
    }

    return parent::createAccess($entity_bundle, $account, $context);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $node, $operation, $langcode, AccountInterface $account) {
    // Fetch information from the node object if possible.
    $status = $node->getTranslation($langcode)->isPublished();
    $uid = $node->getTranslation($langcode)->getAuthorId();

    // Check if authors can view their own unpublished nodes.
    if ($operation === 'view' && !$status && user_access('view own unpublished content', $account)) {

      if ($account->id() != 0 && $account->id() == $uid) {
        return TRUE;
      }
    }

    // If no module specified either allow or deny, we fall back to the
    // node_access table.
    if (($grants = $this->grantStorage->access($node, $operation, $langcode, $account)) !== NULL) {
      return $grants;
    }

    // If no modules implement hook_node_grants(), the default behavior is to
    // allow all users to view published nodes, so reflect that here.
    if ($operation === 'view') {
      return $status;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $configured_types = node_permissions_get_configured_types();
    if (isset($configured_types[$entity_bundle])) {
      return user_access('create ' . $entity_bundle . ' content', $account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function acquireGrants(NodeInterface $node) {
    $grants = $this->moduleHandler->invokeAll('node_access_records', array($node));
    // Let modules alter the grants.
    $this->moduleHandler->alter('node_access_records', $grants, $node);
    // If no grants are set and the node is published, then use the default grant.
    if (empty($grants) && $node->isPublished()) {
      $grants[] = array('realm' => 'all', 'gid' => 0, 'grant_view' => 1, 'grant_update' => 0, 'grant_delete' => 0);
    }
    return $grants;
  }

  /**
   * {@inheritdoc}
   */
  public function writeGrants(NodeInterface $node, $delete = TRUE) {
    $grants = $this->acquireGrants($node);
    $this->grantStorage->write($node, $grants, NULL, $delete);
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefaultGrant() {
    $this->grantStorage->writeDefault();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteGrants() {
    $this->grantStorage->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function countGrants() {
    return $this->grantStorage->count();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAllGrants(AccountInterface $account) {
    return $this->grantStorage->checkAll($account);
  }

}
