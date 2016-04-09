<?php

namespace Drupal\path\Plugin\migrate\source\d6;

use Drupal\path\Plugin\migrate\source\UrlAliasBase;

/**
 * URL aliases source from database.
 *
 * @MigrateSource(
 *   id = "d6_url_alias",
 *   source_provider = "path"
 * )
 */
class UrlAlias extends UrlAliasBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['src'] = $this->t('The internal system path.');
    $fields['dst'] = $this->t('The path alias.');
    return $fields;
  }

}
