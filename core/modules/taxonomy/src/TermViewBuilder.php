<?php

namespace Drupal\taxonomy;

@trigger_error(__NAMESPACE__ . '\TermViewBuilder is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityViewBuilder instead. See https://www.drupal.org/node/2924233.', E_USER_DEPRECATED);

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for taxonomy terms.
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\Entity\EntityViewBuilder instead.
 *
 * @see \Drupal\Core\Entity\EntityViewBuilder
 * @see https://www.drupal.org/node/2924233
 */
class TermViewBuilder extends EntityViewBuilder {}
