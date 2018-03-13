<?php

namespace Drupal\book\Plugin\migrate\source\d6;

use Drupal\book\Plugin\migrate\source\Book as BookGeneral;

@trigger_error('Book is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.x. Use \Drupal\book\Plugin\migrate\source\Book instead. See https://www.drupal.org/node/2947487 for more information.', E_USER_DEPRECATED);

/**
 * Drupal 6 book source.
 *
 * @MigrateSource(
 *   id = "d6_book",
 *   source_module = "book"
 * )
 *
 * @deprecated in Drupal 8.6.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\book\Plugin\migrate\source\Book instead. See
 * https://www.drupal.org/node/2947487 for more information.
 */
class Book extends BookGeneral {}
