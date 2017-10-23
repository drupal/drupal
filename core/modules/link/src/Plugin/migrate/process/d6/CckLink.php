<?php

namespace Drupal\link\Plugin\migrate\process\d6;

use Drupal\link\Plugin\migrate\process\FieldLink;

@trigger_error('CckLink is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\link\Plugin\migrate\process\FieldLink instead.', E_USER_DEPRECATED);

/**
 * @MigrateProcessPlugin(
 *   id = "d6_cck_link"
 * )
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\link\Plugin\migrate\process\FieldLink instead.
 */
class CckLink extends FieldLink {}
