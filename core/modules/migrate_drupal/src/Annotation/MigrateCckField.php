<?php

namespace Drupal\migrate_drupal\Annotation;

@trigger_error('MigrateCckField is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateField instead.', E_USER_DEPRECATED);

/**
 * Deprecated: Defines a cckfield plugin annotation object.
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\migrate_drupal\Annotation\MigrateField instead.
 *
 * Plugin Namespace: Plugin\migrate\cckfield
 *
 * @see https://www.drupal.org/node/2751897
 *
 * @Annotation
 */
class MigrateCckField extends MigrateField {

}
