<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

@trigger_error('The ' . __NAMESPACE__ . '\i18nVariable is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . '\VariableTranslation', E_USER_DEPRECATED);

/**
 * Drupal i18n_variable source from database.
 *
 * @MigrateSource(
 *   id = "i18n_variable",
 *   source_module = "system",
 * )
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. Use
 * \Drupal\migrate_drupal\Plugin\migrate\source\d6\VariableTranslation instead.
 *
 * @see https://www.drupal.org/node/2898649
 */
class i18nVariable extends VariableTranslation {}
