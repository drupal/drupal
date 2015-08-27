<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\source\d6\ProfileFieldValues.
 */

namespace Drupal\user\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 profile fields values source.
 *
 * @MigrateSource(
 *   id = "d6_profile_field_values",
 *   source_provider = "profile"
 * )
 */
class ProfileFieldValues extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('profile_values', 'pv')
      ->distinct()
      ->fields('pv', array('fid', 'uid'));

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Find profile values for this row.
    $query = $this->select('profile_values', 'pv')
      ->fields('pv', array('fid', 'value'));
    $query->leftJoin('profile_fields', 'pf', 'pf.fid=pv.fid');
    $query->fields('pf', array('name', 'type'));
    $query->condition('uid', $row->getSourceProperty('uid'));
    $results = $query->execute();

    foreach ($results as $profile_value) {
      // Check special case for date. We need to unserialize.
      if ($profile_value['type'] == 'date') {
        $date = unserialize($profile_value['value']);
        $date = date('Y-m-d', mktime(0, 0, 0, $date['month'], $date['day'], $date['year']));
        $row->setSourceProperty($profile_value['name'], array('value' => $date));
      }
      elseif ($profile_value['type'] == 'list') {
        // Explode by newline and comma.
        $row->setSourceProperty($profile_value['name'], preg_split("/[\r\n,]+/", $profile_value['value']));
      }
      else {
        $row->setSourceProperty($profile_value['name'], array($profile_value['value']));
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'fid' => $this->t('Unique profile field ID.'),
      'uid' => $this->t('The user Id.'),
      'value' => $this->t('The value for this field.'),
    );

    $query = $this->select('profile_values', 'pv')
      ->fields('pv', array('fid', 'value'));
    $query->leftJoin('profile_fields', 'pf', 'pf.fid=pv.fid');
    $query->fields('pf', array('name', 'title'));
    $results = $query->execute();
    foreach ($results as $profile) {
      $fields[$profile['name']] = $this->t($profile['title']);
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'uid' => array(
        'type' => 'integer',
        'alias' => 'pv',
      ),
    );
  }

}
