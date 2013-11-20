<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\DatabaseRowInterface.
 */

namespace Drupal\migrate\Tests;

interface DatabaseRowInterface {

  function getValue($field);
}
