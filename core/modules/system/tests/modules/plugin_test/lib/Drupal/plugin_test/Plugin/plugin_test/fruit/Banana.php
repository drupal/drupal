<?php

/**
 * @file
 * Contains \Drupal\plugin_test\Plugin\plugin_test\fruit\Banana.
 */

namespace Drupal\plugin_test\Plugin\plugin_test\fruit;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * @Plugin(
 *   id = "banana",
 *   label = "Banana",
 *   color = "yellow",
 *   uses = {
 *     "bread" = @Translation("Banana bread")
 *   }
 * )
 */
class Banana {

}
