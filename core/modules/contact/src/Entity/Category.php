<?php

/**
 * @file
 * Definition of Drupal\contact\Entity\Category.
 */

namespace Drupal\contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\contact\CategoryInterface;

/**
 * Defines the contact category entity.
 *
 * @ConfigEntityType(
 *   id = "contact_category",
 *   label = @Translation("Contact category"),
 *   controllers = {
 *     "access" = "Drupal\contact\CategoryAccessController",
 *     "list_builder" = "Drupal\contact\CategoryListBuilder",
 *     "form" = {
 *       "add" = "Drupal\contact\CategoryForm",
 *       "edit" = "Drupal\contact\CategoryForm",
 *       "delete" = "Drupal\contact\Form\CategoryDeleteForm"
 *     }
 *   },
 *   config_prefix = "category",
 *   admin_permission = "administer contact forms",
 *   bundle_of = "contact_message",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "entity.contact_category.delete_form",
 *     "edit-form" = "entity.contact_category.edit_form"
 *   }
 * )
 */
class Category extends ConfigEntityBundleBase implements CategoryInterface {

  /**
   * The category ID.
   *
   * @var string
   */
  public $id;

  /**
   * The category label.
   *
   * @var string
   */
  public $label;

  /**
   * List of recipient email addresses.
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
