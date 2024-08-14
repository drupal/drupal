<?php

namespace Drupal\search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SearchPlugin type annotation object.
 *
 * SearchPlugin classes define search types for the core Search module. Each
 * search type can be used to create search pages from the Search settings page.
 *
 * @see SearchPluginBase
 *
 * @ingroup search
 *
 * @Annotation
 */
class SearchPlugin extends Plugin {

  /**
   * A unique identifier for the search plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The title for the search page tab.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @todo This will potentially be translated twice or cached with the wrong
   *   translation until the search tabs are converted to local task plugins.
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Whether or not search results should be displayed in admin theme.
   *
   * @var bool
   */
  public $use_admin_theme = FALSE;

}
