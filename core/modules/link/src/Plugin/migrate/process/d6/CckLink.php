<?php

namespace Drupal\link\Plugin\migrate\process\d6;

@trigger_error('CckLink is deprecated in Drupal 8.3.x and will be removed before
Drupal 9.0.x. Use \Drupal\link\Plugin\migrate\process\d6\FieldLink instead.',
E_USER_DEPRECATED);

/**
 * @MigrateProcessPlugin(
 *   id = "d6_cck_link"
 * )
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\link\Plugin\migrate\process\d6\FieldLink instead.
 */
class CckLink extends FieldLink {}
