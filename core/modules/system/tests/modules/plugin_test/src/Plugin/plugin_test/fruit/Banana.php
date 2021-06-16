<?php

namespace Drupal\plugin_test\Plugin\plugin_test\fruit;

/**
 * @Plugin(
 *   id = "banana",
 *   label = "Banana",
 *   color = "yellow",
 *   uses = {
 *     "bread" = @Translation("Banana bread"),
 *     "loaf" = @PluralTranslation(
 *       singular = "@count loaf",
 *       plural = "@count loaves"
 *     )
 *   }
 * )
 */
class Banana implements FruitInterface {

}
