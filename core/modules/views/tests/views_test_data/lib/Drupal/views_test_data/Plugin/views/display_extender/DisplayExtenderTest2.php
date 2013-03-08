<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\display_extender\DisplayExtenderTest2.
 */

namespace Drupal\views_test_data\Plugin\views\display_extender;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines another display extender test plugin.
 *
 * @Plugin(
 *   id = "display_extender_test_2",
 *   title = @Translation("Display extender test number two")
 * )
 */
class DisplayExtenderTest2 extends DisplayExtenderTest {

}
