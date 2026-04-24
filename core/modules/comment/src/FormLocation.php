<?php

declare(strict_types=1);

namespace Drupal\comment;

/**
 * Options for comment form display.
 */
enum FormLocation: int {

  // Comment form should be displayed on a separate page.
  case SeparatePage = 0;

  // Comment form should be shown below post or list of comments.
  case Below = 1;

}
