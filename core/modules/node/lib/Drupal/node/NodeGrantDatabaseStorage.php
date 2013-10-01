<?php

/**
 * @file
 * Contains \Drupal\node\NodeGrantDatabaseStorage.
 */

namespace Drupal\node;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller class that handles the node grants system.
 *
 * This is used to build node query access.
 */
class NodeGrantDatabaseStorage implements NodeGrantDatabaseStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a NodeAccessController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $node, $operation, $langcode, AccountInterface $account) {
    // If no module implements the hook or the node does not have an id there is
    // no point in querying the database for access grants.
    if (!$this->moduleHandler->getImplementations('node_grants') || !$node->id()) {
      return;
    }

    // Check the database for potential access grants.
    $query = $this->database->select('node_access');
    $query->addExpression('1');
    // Only interested for granting in the current operation.
    $query->condition('grant_' . $operation, 1, '>=');
    // Check for grants for this node and the correct langcode.
    $nids = $query->andConditionGroup()
      ->condition('nid', $node->id())
      ->condition('langcode', $langcode);
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

    $grants = $query->orConditionGroup();
    foreach (node_access_grants($operation, $account) as $realm => $gids) {
      foreach ($gids as $gid) {
        $grants->condition(db_and()
            ->condition('gid', $gid)
            ->condition('realm', $realm));
      }
    }

    if (count($grants) > 0) {
      $query->condition($grants);
    }

    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAll(AccountInterface $account) {
    $query = $this->database->select('node_access');
    $query->addExpression('COUNT(*)');
    $query
      ->condition('nid', 0)
      ->condition('grant_view', 1, '>=');

    $grants = db_or();
    foreach (node_access_grants('view', $account) as $realm => $gids) {
      foreach ($gids as $gid) {
        $grants->condition(db_and()
            ->condition('gid', $gid)
            ->condition('realm', $realm)
        );
      }
    }
    if (count($grants) > 0 ) {
      $query->condition($grants);
    }
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function alterQuery($query, array $tables, $op, AccountInterface $account, $base_table) {
    if (!$langcode = $query->getMetaData('langcode')) {
      $langcode = FALSE;
    }

    // Find all instances of the base table being joined -- could appear
    // more than once in the query, and could be aliased. Join each one to
    // the node_access table.
    $grants = node_access_grants($op, $account);
    foreach ($tables as $nalias => $tableinfo) {
      $table = $tableinfo['table'];
      if (!($table instanceof SelectInterface) && $table == $base_table) {
        // Set the subquery.
        $subquery = $this->database->select('node_access', 'na')
          ->fields('na', array('nid'));

        $grant_conditions = db_or();
        // If any grant exists for the specified user, then user has access to the
        // node for the specified operation.
        foreach ($grants as $realm => $gids) {
          foreach ($gids as $gid) {
            $grant_conditions->condition(db_and()
                ->condition('na.gid', $gid)
                ->condition('na.realm', $realm)
            );
          }
        }

        // Attach conditions to the subquery for nodes.
        if (count($grant_conditions->conditions())) {
          $subquery->condition($grant_conditions);
        }
        $subquery->condition('na.grant_' . $op, 1, '>=');

        // Add langcode-based filtering if this is a multilingual site.
        if (language_multilingual()) {
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
        $subquery->where("$nalias.$field = na.nid");

        $query->exists($subquery);
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
        $query->condition('realm', array($realm, 'all'), 'IN');
      }
      $query->execute();
    }
    // Only perform work when node_access modules are active.
    if (!empty($grants) && count($this->moduleHandler->getImplementations('node_grants'))) {
      $query = $this->database->insert('node_access')->fields(array('nid', 'langcode', 'fallback', 'realm', 'gid', 'grant_view', 'grant_update', 'grant_delete'));
      // If we have defined a granted langcode, use it. But if not, add a grant
      // for every language this node is translated to.
      foreach ($grants as $grant) {
        if ($realm && $realm != $grant['realm']) {
          continue;
        }
        if (isset($grant['langcode'])) {
          $grant_languages = array($grant['langcode'] => language_load($grant['langcode']));
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
            if ($grant['langcode'] == $node->language()->id) {
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
    $this->database->delete('node_access')->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefault() {
    $this->database->insert('node_access')
      ->fields(array(
          'nid' => 0,
          'realm' => 'all',
          'gid' => 0,
          'grant_view' => 1,
          'grant_update' => 0,
          'grant_delete' => 0,
        ))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->database->query('SELECT COUNT(*) FROM {node_access}')->fetchField();
  }

}
