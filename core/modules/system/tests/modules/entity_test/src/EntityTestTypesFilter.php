<?php

declare(strict_types=1);

namespace Drupal\entity_test;

/**
 * Enumeration of test entity types filter.
 */
enum EntityTestTypesFilter: int {

  // Filter that limits test entity list to revisable ones.
  case Revisable = 1;

  // Filter that limits test entity list to multilingual ones.
  case Multilingual = 2;

  // Filter that limits test entity list to ones that can be routed.
  case Routing = 3;

}
