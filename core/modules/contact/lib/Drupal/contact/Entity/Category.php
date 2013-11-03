<?php

/**
 * @file
 * Definition of Drupal\contact\Entity\Category.
 */

namespace Drupal\contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\contact\CategoryInterface;

/**
 * Defines the contact category entity.
 *
 * @EntityType(
 *   id = "contact_category",
 *   label = @Translation("Contact category"),
 *   controllers = {
 *     "storage" = "Drupal\contact\CategoryStorageController",
 *     "access" = "Drupal\contact\CategoryAccessController",
 *     "list" = "Drupal\contact\CategoryListController",
 *     "form" = {
 *       "add" = "Drupal\contact\CategoryFormController",
 *       "edit" = "Drupal\contact\CategoryFormController",
 *       "delete" = "Drupal\contact\Form\CategoryDeleteForm"
 *     }
 *   },
 *   config_prefix = "contact.category",
 *   bundle_of = "contact_message",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "admin/structure/contact/manage/{contact_category}"
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

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    if (!$update) {
      entity_invoke_bundle_hook('create', 'contact_message', $this->id());
    }
    elseif ($this->original->id() != $this->id()) {
      entity_invoke_bundle_hook('rename', 'contact_message', $this->original->id(), $this->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    foreach ($entities as $entity) {
      entity_invoke_bundle_hook('delete', 'contact_message', $entity->id());
    }
  }

}
