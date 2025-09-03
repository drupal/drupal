<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\language\ConfigurableLanguageInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Entity hook implementations for node.
 */
class NodeEntityHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_view_display_alter().
   */
  #[Hook('entity_view_display_alter')]
  public function entityViewDisplayAlter(EntityViewDisplayInterface $display, $context): void {
    if ($context['entity_type'] == 'node') {
      // Hide field labels in search index.
      if ($context['view_mode'] == 'search_index') {
        foreach ($display->getComponents() as $name => $options) {
          if (isset($options['label'])) {
            $options['label'] = 'hidden';
            $display->setComponent($name, $options);
          }
        }
      }
    }
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $extra = [];
    $description = $this->t('Node module element');
    foreach (NodeType::loadMultiple() as $bundle) {
      $extra['node'][$bundle->id()]['display']['links'] = [
        'label' => $this->t('Links'),
        'description' => $description,
        'weight' => 100,
        'visible' => TRUE,
      ];
    }
    return $extra;
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for user entities.
   */
  #[Hook('user_predelete')]
  public function userPredelete($account): void {
    // Delete nodes (current revisions).
    // @todo Introduce node_mass_delete() or make NodeBulkUpdate::process() more flexible.
    $nids = \Drupal::entityQuery('node')->condition('uid', $account->id())->accessCheck(FALSE)->execute();
    // Delete old revisions.
    $storage_controller = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $storage_controller->loadMultiple($nids);
    $storage_controller->delete($nodes);
    $query = $storage_controller->getQuery()
      ->allRevisions()
      ->accessCheck(FALSE)
      ->condition('uid', $account->id());
    $revisions = array_keys($query->execute());
    foreach ($revisions as $revision) {
      $storage_controller->deleteRevision($revision);
    }
  }

  /**
   * @defgroup node_access Node access rights
   * @{
   * The node access system determines who can do what to which nodes.
   *
   * In determining access rights for an existing node,
   * \Drupal\node\NodeAccessControlHandler first checks whether the user has the
   * "bypass node access" permission. Such users have unrestricted access to all
   * nodes. user 1 will always pass this check.
   *
   * Next, all implementations of hook_ENTITY_TYPE_access() for node will be
   * called. Each implementation may explicitly allow, explicitly forbid, or
   * ignore the access request. If at least one module says to forbid the
   * request, it will be rejected. If no modules deny the request and at least
   * one says to allow it, the request will be permitted.
   *
   * If all modules ignore the access request, then the node_access table is
   * used to determine access. All node access modules are queried using
   * hook_node_grants() to assemble a list of "grant IDs" for the user. This
   * list is compared against the table. If any row contains the node ID in
   * question (or 0, which stands for "all nodes"), one of the grant IDs
   * returned, and a value of TRUE for the operation in question, then access is
   * granted. Note that this table is a list of grants; any matching row is
   * sufficient to grant access to the node.
   *
   * In node listings (lists of nodes generated from a select query, such as the
   * default home page at path 'node', an RSS feed, a recent content block,
   * etc.), the process above is followed except that hook_ENTITY_TYPE_access()
   * is not called on each node for performance reasons and for proper
   * functioning of the pager system. When adding a node listing to your module,
   * be sure to use an entity query, which will add a tag of "node_access". This
   * will allow modules dealing with node access to ensure only nodes to which
   * the user has access are retrieved, through the use of
   * hook_query_TAG_alter(). See the @link entity_api Entity API topic @endlink
   * for more information on entity queries. Tagging a query with "node_access"
   * does not check the published/unpublished status of nodes, so the base query
   * is responsible for ensuring that unpublished nodes are not displayed to
   * inappropriate users.
   *
   * Note: Even a single module returning an AccessResultInterface object from
   * hook_ENTITY_TYPE_access() whose isForbidden() method equals TRUE will block
   * access to the node. Therefore, implementers should take care to not deny
   * access unless they really intend to. Unless a module wishes to actively
   * forbid access it should return an AccessResultInterface object whose
   * isAllowed() nor isForbidden() methods return TRUE, to allow other modules
   * or the node_access table to control access.
   *
   * Note also that access to create nodes is handled by
   * hook_ENTITY_TYPE_create_access().
   *
   * @see \Drupal\node\NodeAccessControlHandler
   */

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('node_access')]
  public function nodeAccess(NodeInterface $node, $operation, AccountInterface $account): AccessResultInterface {
    $type = $node->bundle();
    // Note create access is handled by hook_ENTITY_TYPE_create_access().
    switch ($operation) {
      case 'update':
        $access = AccessResult::allowedIfHasPermission($account, 'edit any ' . $type . ' content');
        if (!$access->isAllowed() && $account->hasPermission('edit own ' . $type . ' content')) {
          $access = $access->orIf(AccessResult::allowedIf($account->id() == $node->getOwnerId())->cachePerUser()->addCacheableDependency($node));
        }
        break;

      case 'delete':
        $access = AccessResult::allowedIfHasPermission($account, 'delete any ' . $type . ' content');
        if (!$access->isAllowed() && $account->hasPermission('delete own ' . $type . ' content')) {
          $access = $access->orIf(AccessResult::allowedIf($account->id() == $node->getOwnerId()))->cachePerUser()->addCacheableDependency($node);
        }
        break;

      default:
        $access = AccessResult::neutral();
    }
    return $access;
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'configurable_language'.
   */
  #[Hook('configurable_language_delete')]
  public function configurableLanguageDelete(ConfigurableLanguageInterface $language): void {
    // On nodes with this language, unset the language.
    \Drupal::entityTypeManager()->getStorage('node')->clearRevisionsLanguage($language);
  }

}
