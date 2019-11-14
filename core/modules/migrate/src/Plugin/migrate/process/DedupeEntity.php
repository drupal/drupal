<?php

namespace Drupal\migrate\Plugin\migrate\process;

@trigger_error('The ' . __NAMESPACE__ . ' \DedupeEntity is deprecated in
Drupal 8.4.x and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . ' \MakeUniqueEntityField', E_USER_DEPRECATED);

/**
 * Ensures value is not duplicated against an entity field.
 *
 * If the 'migrated' configuration value is true, an entity will only be
 * considered a duplicate if it was migrated by the current migration.
 *
 * @link https://www.drupal.org/node/2135325 Online handbook documentation for dedupe_entity process plugin @endlink
 *
 * @MigrateProcessPlugin(
 *   id = "dedupe_entity"
 * )
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\migrate\Plugin\migrate\process\MakeUniqueEntityField instead.
 *
 * @see https://www.drupal.org/node/2873762
 */
class DedupeEntity extends MakeUniqueEntityField {}
