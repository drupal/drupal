<?php

namespace Drupal\views\Plugin\views\style;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;

/**
 * Unformatted style plugin to render rows.
 *
 * Row are rendered one after another with no decorations.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: "default",
  title: new TranslatableMarkup("Unformatted list"),
  help: new TranslatableMarkup("Displays rows one after another."),
  theme: "views_view_unformatted",
  display_types: ["normal"],
)]
class DefaultStyle extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

}
