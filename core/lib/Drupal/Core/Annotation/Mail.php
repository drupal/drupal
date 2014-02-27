<?php

/**
 * @file
 * Contains \Drupal\Core\Annotation\Mail.
 */

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Mail annotation object.
 *
 * @Annotation
 */
class Mail extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the mail plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the mail plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
