<?php

/**
 * @file
 * Definition of Drupal\contact\Plugin\Core\Entity\Category.
 */

namespace Drupal\contact\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\contact\CategoryInterface;

/**
 * Defines the contact category entity.
 *
 * @EntityType(
 *   id = "contact_category",
 *   label = @Translation("Contact category"),
 *   module = "contact",
 *   controllers = {
 *     "storage" = "Drupal\contact\CategoryStorageController",
 *     "access" = "Drupal\contact\CategoryAccessController",
 *     "list" = "Drupal\contact\CategoryListController",
 *     "form" = {
 *       "add" = "Drupal\contact\CategoryFormController",
 *       "edit" = "Drupal\contact\CategoryFormController"
 *     }
 *   },
 *   uri_callback = "contact_category_uri",
 *   config_prefix = "contact.category",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Category extends ConfigEntityBase implements CategoryInterface {

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
