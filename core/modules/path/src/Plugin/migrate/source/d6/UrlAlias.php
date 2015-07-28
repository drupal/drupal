<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\migrate\source\d6\UrlAlias.
 */

namespace Drupal\path\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 url aliases source from database.
 *
 * @MigrateSource(
 *   id = "d6_url_alias"
 * )
 */
class UrlAlias extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('url_alias', 'ua')
      ->fields('ua', array('pid', 'src', 'dst', 'language'));
    $query->orderBy('pid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'pid' => $this->t('The numeric identifier of the path alias.'),
      'src' => $this->t('The internal path.'),
      'dst' => $this->t('The user set path alias.'),
      'language' => $this->t('The language code of the url alias.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['pid']['type'] = 'integer';
    return $ids;
  }

}
