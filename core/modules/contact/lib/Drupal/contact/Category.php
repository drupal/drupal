<?php

/**
 * @file
 * Definition of Drupal\contact\Category.
 */

namespace Drupal\contact;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the contact category entity.
 */
class Category extends ConfigEntityBase {

  /**
   * The category ID.
   *
   * @var string
   */
  public $id;

  /**
   * The category UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The category label.
   *
   * @var string
   */
  public $label;

  /**
   * List of recipient e-mail addresses.
   *
   * @var array
   */
  public $recipients = array();

  /**
   * An auto-reply message to send to the message author.
   *
   * @var string
   */
  public $reply = '';

  /**
   * Weight of this category (used for sorting).
   *
   * @var int
   */
  public $weight = 0;

}
