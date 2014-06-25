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
 * Plugin Namespace: Plugin\Mail
 *
 * For a working example, see \Drupal\Core\Mail\Plugin\Mail\PhpMail
 *
 * @see \Drupal\Core\Mail\MailInterface
 * @see \Drupal\Core\Mail\MailManager
 * @see plugin_api
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
