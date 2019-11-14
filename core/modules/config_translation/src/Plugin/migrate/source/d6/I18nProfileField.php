<?php

namespace Drupal\config_translation\Plugin\migrate\source\d6;

@trigger_error('The ' . __NAMESPACE__ . '\I18nProfileField is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . '\ProfileFieldTranslation', E_USER_DEPRECATED);

/**
 * i18n strings profile field source from database.
 *
 * @MigrateSource(
 *   id = "d6_i18n_profile_field",
 *   source_module = "i18nprofile"
 * )
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. Use
 * \Drupal\config_translation\Plugin\migrate\source\d6\ProfileFieldTranslation
 * instead.
 *
 * @see https://www.drupal.org/node/2898649
 */
class I18nProfileField extends ProfileFieldTranslation {}
