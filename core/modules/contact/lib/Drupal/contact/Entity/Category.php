<?php

/**
 * @file
 * Definition of Drupal\contact\Entity\Category.
 */

namespace Drupal\contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
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
 *       "add" = "Drupal\contact\CategoryFormController",
 *       "edit" = "Drupal\contact\CategoryFormController",
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
 *     "delete-form" = "contact.category_delete",
 *     "edit-form" = "contact.category_edit"
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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

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
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $entity) {
      entity_invoke_bundle_hook('delete', 'contact_message', $entity->id());
    }
  }

}
