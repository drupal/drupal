<?php

declare(strict_types=1);

namespace Drupal\Core\File;

/**
 * A flag for defining the behavior when dealing with existing files.
 */
enum FileExists {

  /* Appends a number until name is unique. */
  case Rename;
  /* Replace the existing file. */
  case Replace;
  /* Do nothing and return FALSE. */
  case Error;

}
