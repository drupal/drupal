<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\d6\Role.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;


use Drupal\migrate\Row;

/**
 * Drupal 6 role source from database.
 *
 * @PluginId("drupal6_user_role")
 */
class Role extends Drupal6SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('role', 'r')
      ->fields('r', array('rid', 'name'))
      ->orderBy('rid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'rid' => t('Role ID.'),
      'name' => t('The name of the user role.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  function prepareRow(Row $row, $keep = TRUE) {
    $permissions = array();
    $results = $this->database
      ->select('permission', 'p', array('fetch' => \PDO::FETCH_ASSOC))
      ->fields('p', array('pid', 'rid', 'perm', 'tid'))
      ->condition('rid', $row->getSourceProperty('rid'))
      ->execute();
    foreach ($results as $perm) {
      $permissions[] = array(
        'pid' => $perm['pid'],
        'rid' => $perm['rid'],
        'perm' => array_map('trim', explode(',', $perm['perm'])),
        'tid' => $perm['tid'],
      );
    }
    $row->setSourceProperty('permissions', $permissions);
    return parent::prepareRow($row);
  }

}
