<?php

/**
 * @file
 * Contains \Drupal\search\Annotation\SearchPlugin.
 */

namespace Drupal\search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SearchPlugin type annotation object.
 *
 * SearchPlugin classes define search types for the core Search module. Each
 * active search type is displayed in a tab on the Search page, and each has a
 * path suffix after "search/".
 *
 * @see SearchPluginBase
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
   * The path fragment to be added to search/ for the search page.
   *
   * @var string
   */
  public $path;

  /**
   * The title for the search page tab.
   *
   * @todo This will potentially be translated twice or cached with the wrong
   *   translation until the search tabs are converted to local task plugins.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;
}
