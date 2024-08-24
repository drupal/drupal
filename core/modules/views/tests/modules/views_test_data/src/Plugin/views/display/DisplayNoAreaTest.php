<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Plugin\views\display;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsDisplay;

/**
 * Defines a Display test plugin with areas disabled.
 */
#[ViewsDisplay(
  id: "display_no_area_test",
  title: new TranslatableMarkup("Display test no area"),
  help: new TranslatableMarkup("Defines a display test with areas disabled."),
  theme: "views_view",
  register_theme: FALSE,
  contextual_links_locations: ["view"]
)]
class DisplayNoAreaTest extends DisplayTest {

  /**
   * Whether the display allows area plugins.
   *
   * @var bool
   *   TRUE if the display can use areas, or FALSE otherwise.
   */
  protected $usesAreas = FALSE;

}
