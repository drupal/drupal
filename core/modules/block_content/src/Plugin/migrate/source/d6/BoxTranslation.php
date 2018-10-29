<?php

namespace Drupal\block_content\Plugin\migrate\source\d6;

use Drupal\block_content\Plugin\migrate\source\d7\BlockCustomTranslation as D7BlockCustomTranslation;

/**
 * Gets Drupal 6 i18n custom block translations from database.
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
