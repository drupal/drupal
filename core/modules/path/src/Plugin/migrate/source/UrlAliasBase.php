<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\migrate\source\UrlAliasBase.
 */

namespace Drupal\path\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Base class for the url_alias source plugins.
 */
abstract class UrlAliasBase extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('url_alias', 'ua')->fields('ua');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'pid' => $this->t('The numeric identifier of the path alias.'),
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
