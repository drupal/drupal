<?php

namespace Drupal\taxonomy\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\EntityArgument;

/**
 * Argument handler for basic taxonomy tid.
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(
  id: 'taxonomy',
)]
class Taxonomy extends EntityArgument {}
