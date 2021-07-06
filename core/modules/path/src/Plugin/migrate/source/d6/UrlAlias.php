<?php

namespace Drupal\path\Plugin\migrate\source\d6;

use Drupal\path\Plugin\migrate\source\UrlAliasBase;

/**
 * Drupal 6 URL aliases source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_url_alias",
 *   source_module = "path"
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
