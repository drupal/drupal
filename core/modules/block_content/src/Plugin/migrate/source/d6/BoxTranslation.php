<?php

namespace Drupal\block_content\Plugin\migrate\source\d6;

use Drupal\block_content\Plugin\migrate\source\d7\BlockCustomTranslation as D7BlockCustomTranslation;

/**
 * Drupal 6 i18n content block translations source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_box_translation",
 *   source_module = "i18nblocks"
 * )
 */
class BoxTranslation extends D7BlockCustomTranslation {

  /**
   * Drupal 6 table names.
   */
  const CUSTOM_BLOCK_TABLE = 'boxes';
  const I18N_STRING_TABLE = 'i18n_strings';

}
