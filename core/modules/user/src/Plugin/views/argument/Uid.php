<?php

namespace Drupal\user\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\EntityArgument;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(
  id: 'user_uid'
)]
class Uid extends EntityArgument {}
