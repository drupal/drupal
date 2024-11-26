<?php

declare(strict_types = 1);

namespace Drupal\navigation;

/**
 * Enumeration of the Top Bar regions.
 */
enum TopBarRegion: string {
  case Tools = 'tools';
  case Context = 'context';
  case Actions = 'actions';
}
