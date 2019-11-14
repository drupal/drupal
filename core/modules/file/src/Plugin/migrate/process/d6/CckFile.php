<?php

namespace Drupal\file\Plugin\migrate\process\d6;

@trigger_error('CckFile is deprecated in Drupal 8.3.x and will be be removed before Drupal 9.0.x. Use \Drupal\file\Plugin\migrate\process\d6\FieldFile instead.', E_USER_DEPRECATED);

/**
 * @MigrateProcessPlugin(
 *   id = "d6_cck_file"
 * )
 *
 *  @deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use
 * \Drupal\file\Plugin\migrate\process\d6\FieldFile instead.
 *
 * @see https://www.drupal.org/node/2751897
 */
class CckFile extends FieldFile {}
