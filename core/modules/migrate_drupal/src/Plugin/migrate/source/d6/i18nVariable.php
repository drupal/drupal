<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

@trigger_error('The ' . __NAMESPACE__ . '\i18nVariable is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . '\VariableTranslation', E_USER_DEPRECATED);

/**
 * Drupal i18n_variable source from database.
 *
 * @MigrateSource(
 *   id = "i18n_variable"
 * )
 *
 * @deprecated in Drupal 8.4.x and will be removed in Drupal 9.0.x. Use
 * \Drupal\migrate_drupal\Plugin\migrate\source\d6\VariableTranslation instead.
 *
 * @see https://www.drupal.org/node/2898649
 */
class i18nVariable extends VariableTranslation {}
