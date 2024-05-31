<?php

namespace Drupal\taxonomy\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\EntityArgument;

/**
 * Argument handler to accept a vocabulary id.
 *
 * @ingroup views_argument_handlers
  */
#[ViewsArgument(
  id: 'vocabulary_vid',
)]
class VocabularyVid extends EntityArgument {}
