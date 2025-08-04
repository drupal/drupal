<?php

namespace Drupal\node;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Defines a storage handler class that handles the node grants system.
 *
 * This is used to build node query access.
 *
 * @ingroup node_access
 */
class NodeGrantDatabaseStorage implements NodeGrantDatabaseStorageInterface {

  /**
   * Indicates if any module implements hook_node_grants().
   */
  protected readonly bool $hasNodeGrantsImplementations;

  public function __construct(
    protected readonly Connection $database,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly LanguageManagerInterface $languageManager,
    #[Autowire(service: 'node.view_all_nodes_memory_cache')]
    protected MemoryCacheInterface $memoryCache,
  ) {
    if (!$memoryCache) {
      @trigger_error('Calling NodeGrantDatabaseStorage::__construct() without the $memoryCache argument is deprecated in drupal:11.3.0 and the $memoryCache argument will be required in drupal:12.0.0. See https://www.drupal.org/node/3038909', E_USER_DEPRECATED);
      $this->memoryCache = \Drupal::service('node.view_all_nodes_memory_cache');
    }
    $this->hasNodeGrantsImplementations = $this->moduleHandler->hasImplementations('node_grants');
  }

  /**
   * {@inheritdoc}
   */
  public function access(NodeInterface $node, $operation, AccountInterface $account) {
    // Grants only support these operations.
    if (!in_array($operation, ['view', 'update', 'delete'])) {
      return AccessResult::neutral();
    }

    // If no module implements the hook or the node does not have an id there is
    // no point in querying the database for access grants.
    if (!$this->hasNodeGrantsImplementations || $node->isNew()) {
      // Return the equivalent of the default grant, defined by
      // self::writeDefault().
      if ($operation === 'view') {
        $result = AccessResult::allowedIf($node->isPublished());
        if (!$node->isNew()) {
          $result->addCacheableDependency($node);
        }

        return $result;
      }

      return AccessResult::neutral();
    }

    // Check the database for potential access grants.
    $query = $this->database->select('node_access');
    $query->addExpression('1');
    // Only interested for granting in the current operation.
    $query->condition('grant_' . $operation, 1, '>=');
    // Check for grants for this node and the correct langcode. New translations
    // do not yet have a langcode and must check the fallback node record.
    $nids = $query->andConditionGroup()
      ->condition('nid', $node->id());
    if (!$node->isNewTranslation()) {
      $nids->condition('langcode', $node->language()->getId());
    }
    else {
      $nids->condition('fallback', 1);
    }
    // If the node is published, also take the default grant into account. The
    // default is saved with a node ID of 0.
    $status = $node->isPublished();
    if ($status) {
      $nids = $query->orConditionGroup()
        ->condition($nids)
        ->condition('nid', 0);
    }
    $query->condition($nids);
    $query->range(0, 1);

    $grants = $this->buildGrantsQueryCondition(node_access_grants($operation, $account));

    if (count($grants) > 0) {
      $query->condition($grants);
    }
    if ($query->execute()->fetchField()) {
      $access_result = AccessResult::allowed();
    }
    else {
      $access_result = AccessResult::neutral();
    }
    $access_result->addCacheContexts(['user.node_grants:' . $operation]);

    // Only the 'view' node grant can currently be cached; the others currently
    // don't have any cacheability metadata. Hopefully, we can add that in the
    // future, which would allow this access check result to be cacheable in all
    // cases. For now, this must remain marked as uncacheable, even when it is
    // theoretically cacheable, because we don't have the necessary metadata to
    // know it for a fact.
    if ($operation !== 'view') {
      $access_result->setCacheMaxAge(0);
    }
    return $access_result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAll(AccountInterface $account) {
    // If no modules implement the node access system, access is always 1.
    if (!$this->hasNodeGrantsImplementations) {
      return 1;
    }

    $cacheItem = $this->memoryCache->get($account->id());
    if ($cacheItem) {
      return $cacheItem->data;
    }

    $query = $this->database->select('node_access');
    $query->addExpression('COUNT(*)');
    $query
      ->condition('nid', 0)
      ->condition('grant_view', 1, '>=');

    $grants = $this->buildGrantsQueryCondition(node_access_grants('view', $account));

    if (count($grants) > 0) {
      $query->condition($grants);
    }
    $access = $query->execute()->fetchField();
    $this->memoryCache->set($account->id(), $access);
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function alterQuery($query, array $tables, $operation, AccountInterface $account, $base_table) {
    if (!$langcode = $query->getMetaData('langcode')) {
      $langcode = FALSE;
    }

    // Find all instances of the base table being joined which could appear
    // more than once in the query, and could be aliased. Join each one to
    // the node_access table.
    $grants = node_access_grants($operation, $account);
    // If any grant exists for the specified user, then user has access to the
    // node for the specified operation.
    $grant_conditions = $this->buildGrantsQueryCondition($grants);
    $grants_exist = count($grant_conditions->conditions()) > 0;

    $is_multilingual = \Drupal::languageManager()->isMultilingual();
    foreach ($tables as $table_alias => $tableinfo) {
      $table = $tableinfo['table'];
      if (!($table instanceof SelectInterface) && $table == $base_table) {
        // Set the subquery.
        $subquery = $this->database->select('node_access', 'na')
          ->fields('na', ['nid']);

        // Attach conditions to the sub-query for nodes.
        if ($grants_exist) {
          $subquery->condition($grant_conditions);
        }
        $subquery->condition('na.grant_' . $operation, 1, '>=');

        // Add langcode-based filtering if this is a multilingual site.
        if ($is_multilingual) {
          // If no specific langcode to check for is given, use the grant entry
          // which is set as a fallback.
          // If a specific langcode is given, use the grant entry for it.
          if ($langcode === FALSE) {
            $subquery->condition('na.fallback', 1, '=');
          }
          else {
            $subquery->condition('na.langcode', $langcode, '=');
          }
        }

        $field = 'nid';
        // Now handle entities.
        $subquery->where("[$table_alias].[$field] = [na].[nid]");

        if (empty($tableinfo['join type'])) {
          $query->exists($subquery);
        }
        else {
          // If this is a join, add the node access check to the join condition.
          // This requires using $query->getTables() to alter the table
          // information.
          $join_cond = $query
            ->andConditionGroup()
            ->exists($subquery);
          $join_cond->where($tableinfo['condition'], $query->getTables()[$table_alias]['arguments']);
          $query->getTables()[$table_alias]['arguments'] = [];
          $query->getTables()[$table_alias]['condition'] = $join_cond;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function write(NodeInterface $node, array $grants, $realm = NULL, $delete = TRUE) {
    if ($delete) {
      $query = $this->database->delete('node_access')->condition('nid', $node->id());
      if ($realm) {
        $query->condition('realm', [$realm, 'all'], 'IN');
      }
      $query->execute();
    }
    // Only perform work when node_access modules are active.
    if (!empty($grants) && $this->hasNodeGrantsImplementations) {
      $query = $this->database->insert('node_access')->fields(['nid', 'langcode', 'fallback', 'realm', 'gid', 'grant_view', 'grant_update', 'grant_delete']);
      // If we have defined a granted langcode, use it. But if not, add a grant
      // for every language this node is translated to.
      $fallback_langcode = $node->getUntranslated()->language()->getId();
      foreach ($grants as $grant) {
        if ($realm && $realm != $grant['realm']) {
          continue;
        }
        if (isset($grant['langcode'])) {
          $grant_languages = [$grant['langcode'] => $this->languageManager->getLanguage($grant['langcode'])];
        }
        else {
          $grant_languages = $node->getTranslationLanguages(TRUE);
        }
        foreach ($grant_languages as $grant_langcode => $grant_language) {
          // Only write grants; denies are implicit.
          if ($grant['grant_view'] || $grant['grant_update'] || $grant['grant_delete']) {
            $grant['nid'] = $node->id();
            $grant['langcode'] = $grant_langcode;
            // The record with the original langcode is used as the fallback.
            if ($grant['langcode'] == $fallback_langcode) {
              $grant['fallback'] = 1;
            }
            else {
              $grant['fallback'] = 0;
            }
            $query->values($grant);
          }
        }
      }
      $query->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->database->truncate('node_access')->execute();
    $this->memoryCache->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefault() {
    $this->database->insert('node_access')
      ->fields([
        'nid' => 0,
        'realm' => 'all',
        'gid' => 0,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->database->query('SELECT COUNT(*) FROM {node_access}')->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteNodeRecords(array $nids) {
    $this->database->delete('node_access')
      ->condition('nid', $nids, 'IN')
      ->execute();
  }

  /**
   * Creates a query condition from an array of node access grants.
   *
   * @param array $node_access_grants
   *   An array of grants, as returned by node_access_grants().
   *
   * @return \Drupal\Core\Database\Query\Condition
   *   A condition object to be passed to $query->condition().
   *
   * @see node_access_grants()
   */
  protected function buildGrantsQueryCondition(array $node_access_grants) {
    $grants = $this->database->condition('OR');
    foreach ($node_access_grants as $realm => $gids) {
      if (!empty($gids)) {
        $and = $this->database->condition('AND');
        $grants->condition($and
          ->condition('gid', $gids, 'IN')
          ->condition('realm', $realm)
        );
      }
    }

    return $grants;
  }

}
