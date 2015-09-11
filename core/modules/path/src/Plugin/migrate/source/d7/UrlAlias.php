<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\migrate\source\d7\UrlAlias.
 */

namespace Drupal\path\Plugin\migrate\source\d7;

use Drupal\path\Plugin\migrate\source\UrlAliasBase;

/**
 * URL aliases source from database.
 *
 * @MigrateSource(
 *   id = "d7_url_alias",
 *   source_provider = "path"
 * )
 */
class UrlAlias extends UrlAliasBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['source'] = $this->t('The internal system path.');
    $fields['alias'] = $this->t('The path alias.');
    return $fields;
  }

}
