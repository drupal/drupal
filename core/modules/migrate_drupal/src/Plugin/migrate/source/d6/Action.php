<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\Action.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 action source from database.
 *
 * @MigrateSource(
 *   id = "d6_action"
 * )
 */
class Action extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('actions', 'a')
      ->fields('a', array(
        'aid',
        'type',
        'callback',
        'parameters',
        'description',
      )
    );
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'aid' => $this->t('Action ID'),
      'type' => $this->t('Module'),
      'callback' => $this->t('Callback function'),
      'parameters' => $this->t('Action configuration'),
      'description' => $this->t('Action description'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['aid']['type'] = 'string';
    return $ids;
  }

}
