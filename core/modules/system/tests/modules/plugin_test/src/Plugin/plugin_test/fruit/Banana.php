<?php

/**
 * @file
 * Contains \Drupal\plugin_test\Plugin\plugin_test\fruit\Banana.
 */

namespace Drupal\plugin_test\Plugin\plugin_test\fruit;

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
class Banana implements FruitInterface {

}
