<?php

namespace Drupal\file\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\EntityArgument;

/**
 * Argument handler to accept multiple file ids.
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(
  id: 'file_fid',
)]
class Fid extends EntityArgument {}
