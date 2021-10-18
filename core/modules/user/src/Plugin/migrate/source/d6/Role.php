<?php

namespace Drupal\user\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 role source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_user_role",
 *   source_module = "user"
 * )
 */
class Role extends DrupalSqlBase {

  /**
   * List of filter IDs per role IDs.
   *
   * @var array
   */
  protected $filterPermissions = [];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('role', 'r')
      ->fields('r', ['rid', 'name'])
      ->orderBy('r.rid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'rid' => $this->t('Role ID.'),
      'name' => $this->t('The name of the user role.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $filter_roles = $this->select('filter_formats', 'f')
      ->fields('f', ['format', 'roles'])
      ->execute()
      ->fetchAllKeyed();
    foreach ($filter_roles as $format => $roles) {
      // Drupal 6 code: $roles = ','. implode(',', $roles) .',';
      // Remove the beginning and ending comma.
      foreach (explode(',', trim($roles, ',')) as $rid) {
        $this->filterPermissions[$rid][] = $format;
      }
    }
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $rid = $row->getSourceProperty('rid');
    $permissions = $this->select('permission', 'p')
      ->fields('p', ['perm'])
      ->condition('rid', $rid)
      ->execute()
      ->fetchField();

    // If a role has no permissions then set to an empty array. The role will
    // be migrated and given the default D8 permissions.
    if ($permissions) {
      $row->setSourceProperty('permissions', explode(', ', $permissions));
    }
    else {
      $row->setSourceProperty('permissions', []);
    }
    if (isset($this->filterPermissions[$rid])) {
      $row->setSourceProperty("filter_permissions:$rid", $this->filterPermissions[$rid]);
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['rid']['type'] = 'integer';
    return $ids;
  }

}
