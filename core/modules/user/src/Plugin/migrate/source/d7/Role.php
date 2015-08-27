<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\source\d7\Role.
 */

namespace Drupal\user\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 role source from database.
 *
 * @MigrateSource(
 *   id = "d7_user_role"
 * )
 */
class Role extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('role', 'r')->fields('r');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'rid' => $this->t('Role ID.'),
      'name' => $this->t('The name of the user role.'),
      'weight' => $this->t('The weight of the role.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $permissions = $this->select('role_permission', 'rp')
      ->fields('rp', ['permission'])
      ->condition('rid', $row->getSourceProperty('rid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('permissions', $permissions);

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
