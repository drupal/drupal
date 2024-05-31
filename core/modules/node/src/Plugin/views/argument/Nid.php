<?php

namespace Drupal\node\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\EntityArgument;

/**
 * Argument handler to accept a node id.
 */
#[ViewsArgument(
  id: 'node_nid',
)]
class Nid extends EntityArgument {}
