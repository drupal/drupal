<?php

namespace Drupal\tour\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a tour item annotation object.
 *
 * Plugin Namespace: Plugin\tour\tip
 *
 * For a working example, see \Drupal\tour\Plugin\tour\tip\TipPluginText
 *
 * @see \Drupal\tour\TipPluginBase
 * @see \Drupal\tour\TipPluginInterface
 * @see \Drupal\tour\TipPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Tip extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
