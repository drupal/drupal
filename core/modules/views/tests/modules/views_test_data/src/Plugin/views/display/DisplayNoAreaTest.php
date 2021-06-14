<?php

namespace Drupal\views_test_data\Plugin\views\display;

/**
 * Defines a Display test plugin with areas disabled.
 *
 * @ViewsDisplay(
 *   id = "display_no_area_test",
 *   title = @Translation("Display test no area"),
 *   help = @Translation("Defines a display test with areas disabled."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   contextual_links_locations = {"view"}
 * )
 */
class DisplayNoAreaTest extends DisplayTest {

  /**
   * Whether the display allows area plugins.
   *
   * @var bool
   *   TRUE if the display can use areas, or FALSE otherwise.
   */
  protected $usesAreas = FALSE;

}
