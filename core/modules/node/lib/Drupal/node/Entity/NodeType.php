<?php

/**
 * @file
 * Contains \Drupal\node\Entity\NodeType.
 */

namespace Drupal\node\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Defines the Node type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "node_type",
 *   label = @Translation("Content type"),
 *   controllers = {
 *     "access" = "Drupal\node\NodeTypeAccessController",
 *     "form" = {
 *       "add" = "Drupal\node\NodeTypeFormController",
 *       "edit" = "Drupal\node\NodeTypeFormController",
 *       "delete" = "Drupal\node\Form\NodeTypeDeleteConfirm"
 *     },
 *     "list" = "Drupal\node\NodeTypeListController",
 *   },
 *   admin_permission = "administer content types",
 *   config_prefix = "type",
 *   bundle_of = "node",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name"
 *   },
 *   links = {
 *     "add-form" = "node.add",
 *     "edit-form" = "node.type_edit",
 *     "delete-form" = "node.type_delete_confirm"
 *   }
 * )
 */
class NodeType extends ConfigEntityBase implements NodeTypeInterface {

  /**
   * The machine name of this node type.
   *
   * @var string
   *
   * @todo Rename to $id.
   */
  public $type;

  /**
   * The human-readable name of the node type.
   *
   * @var string
   *
   * @todo Rename to $label.
   */
  public $name;

  /**
   * A brief description of this node type.
   *
   * @var string
   */
  public $description;

  /**
   * Help information shown to the user when creating a Node of this type.
   *
   * @var string
   */
  public $help;

  /**
   * Indicates whether the Node entity of this type has a title.
   *
   * @var bool
   *
   * @todo Rename to $node_has_title.
   */
  public $has_title = TRUE;

  /**
   * The label to use for the title of a Node of this type in the user interface.
   *
   * @var string
   *
   * @todo Rename to $node_title_label.
   */
  public $title_label = 'Title';

  /**
   * Indicates whether a Body field should be created for this node type.
   *
   * This property affects entity creation only. It allows default configuration
   * of modules and installation profiles to specify whether a Body field should
   * be created for this bundle.
   *
   * @var bool
   *
   * @see \Drupal\node\Entity\NodeType::$create_body_label
   */
  protected $create_body = TRUE;

  /**
   * The label to use for the Body field upon entity creation.
   *
   * @see \Drupal\node\Entity\NodeType::$create_body
   *
   * @var string
   */
  protected $create_body_label = 'Body';

  /**
   * Module-specific settings for this node type, keyed by module name.
   *
   * @var array
   *
   * @todo Pluginify.
   */
  public $settings = array();

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleSettings($module) {
    if (isset($this->settings[$module]) && is_array($this->settings[$module])) {
      return $this->settings[$module];
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('node.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    if (!$update) {
      // Clear the node type cache, so the new type appears.
      Cache::deleteTags(array('node_types' => TRUE));

      entity_invoke_bundle_hook('create', 'node', $this->id());

      // Create a body if the create_body property is true and we're not in
      // the syncing process.
      if ($this->get('create_body') && !$this->isSyncing()) {
        $label = $this->get('create_body_label');
        node_add_body_field($this, $label);
      }
    }
    elseif ($this->getOriginalId() != $this->id()) {
      // Clear the node type cache to reflect the rename.
      Cache::deleteTags(array('node_types' => TRUE));

      $update_count = node_type_update_nodes($this->getOriginalId(), $this->id());
      if ($update_count) {
        drupal_set_message(format_plural($update_count,
          'Changed the content type of 1 post from %old-type to %type.',
          'Changed the content type of @count posts from %old-type to %type.',
          array(
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          )));
      }
      entity_invoke_bundle_hook('rename', 'node', $this->getOriginalId(), $this->id());
    }
    else {
      // Invalidate the cache tag of the updated node type only.
      Cache::invalidateTags(array('node_type' => $this->id()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    // Clear the node type cache to reflect the removal.
    $storage_controller->resetCache(array_keys($entities));
    foreach ($entities as $entity) {
      entity_invoke_bundle_hook('delete', 'node', $entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    // Ensure default values are set.
    if (!isset($values['settings']['node'])) {
      $values['settings']['node'] = array();
    }
    $values['settings']['node'] = NestedArray::mergeDeep(array(
      'options' => array(
        'status' => TRUE,
        'promote' => TRUE,
        'sticky' => FALSE,
        'revision' => FALSE,
      ),
      'preview' => DRUPAL_OPTIONAL,
      'submitted' => TRUE,
    ), $values['settings']['node']);
  }

}
