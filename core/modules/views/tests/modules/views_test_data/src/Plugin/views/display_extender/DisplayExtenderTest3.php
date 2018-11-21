<?php

namespace Drupal\views_test_data\Plugin\views\display_extender;

/**
 * Defines the third display extender test plugin.
 *
 * @ViewsDisplayExtender(
 *   id = "display_extender_test_3",
 *   title = @Translation("Display extender test number three")
 * )
 */
class DisplayExtenderTest3 extends DisplayExtenderTest {

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return ['Display extender test error.'];
  }

}
