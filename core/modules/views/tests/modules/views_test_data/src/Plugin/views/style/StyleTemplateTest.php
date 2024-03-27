<?php

namespace Drupal\views_test_data\Plugin\views\style;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Provides a general test style template plugin.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: "test_template_style",
  title: new TranslatableMarkup("Test style template plugin"),
  help: new TranslatableMarkup("Provides a generic style template test plugin."),
  theme: "views_view_style_template_test",
  display_types: ["normal", "test"],
)]
class StyleTemplateTest extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

}
