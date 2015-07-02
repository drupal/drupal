<?php

/**
 * @file
 * Contains \Drupal\rest\Annotation\RestResource.
 */

namespace Drupal\rest\Annotation;

use \Drupal\Component\Annotation\Plugin;

/**
 * Defines a REST resource annotation object.
 *
 * Plugin Namespace: Plugin\rest\resource
 *
 * For a working example, see \Drupal\dblog\Plugin\rest\resource\DBLogResource
 *
 * @see \Drupal\rest\Plugin\Type\ResourcePluginManager
 * @see \Drupal\rest\Plugin\ResourceBase
 * @see \Drupal\rest\Plugin\ResourceInterface
 * @see plugin_api
 *
 * @ingroup third_party
 *
 * @Annotation
 */
class RestResource extends Plugin {

  /**
   * The resource plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the resource plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
