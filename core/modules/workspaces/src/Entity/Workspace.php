<?php

namespace Drupal\workspaces\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerTrait;
use Drupal\workspaces\WorkspaceInterface;

/**
 * The workspace entity class.
 *
 * @ContentEntityType(
 *   id = "workspace",
 *   label = @Translation("Workspace"),
 *   label_collection = @Translation("Workspaces"),
 *   label_singular = @Translation("workspace"),
 *   label_plural = @Translation("workspaces"),
 *   label_count = @PluralTranslation(
 *     singular = "@count workspace",
 *     plural = "@count workspaces"
 *   ),
 *   handlers = {
 *     "list_builder" = "\Drupal\workspaces\WorkspaceListBuilder",
 *     "access" = "Drupal\workspaces\WorkspaceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "\Drupal\workspaces\Form\WorkspaceForm",
 *       "add" = "\Drupal\workspaces\Form\WorkspaceForm",
 *       "edit" = "\Drupal\workspaces\Form\WorkspaceForm",
 *       "delete" = "\Drupal\workspaces\Form\WorkspaceDeleteForm",
 *       "activate" = "\Drupal\workspaces\Form\WorkspaceActivateForm",
 *       "deploy" = "\Drupal\workspaces\Form\WorkspaceDeployForm",
 *     },
 *   },
 *   admin_permission = "administer workspaces",
 *   base_table = "workspace",
 *   revision_table = "workspace_revision",
 *   data_table = "workspace_field_data",
 *   revision_data_table = "workspace_field_revision",
 *   field_ui_base_route = "entity.workspace.collection",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/workflow/workspaces/add",
 *     "edit-form" = "/admin/config/workflow/workspaces/manage/{workspace}/edit",
 *     "delete-form" = "/admin/config/workflow/workspaces/manage/{workspace}/delete",
 *     "activate-form" = "/admin/config/workflow/workspaces/manage/{workspace}/activate",
 *     "deploy-form" = "/admin/config/workflow/workspaces/manage/{workspace}/deploy",
 *     "collection" = "/admin/config/workflow/workspaces",
 *   },
 * )
 */
class Workspace extends ContentEntityBase implements WorkspaceInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Workspace ID'))
      ->setDescription(new TranslatableMarkup('The workspace ID.'))
      ->setSetting('max_length', 128)
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->addConstraint('DeletedWorkspace')
      ->addPropertyConstraints('value', ['Regex' => ['pattern' => '/^[a-z0-9_]+$/']]);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Workspace name'))
      ->setDescription(new TranslatableMarkup('The workspace name.'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 128)
      ->setRequired(TRUE);

    $fields['uid']
      ->setLabel(new TranslatableMarkup('Owner'))
      ->setDescription(new TranslatableMarkup('The workspace owner.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the workspace was last edited.'))
      ->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the workspace was created.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function publish() {
    return \Drupal::service('workspaces.operation_factory')->getPublisher($this)->publish();
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultWorkspace() {
    return $this->id() === static::DEFAULT_WORKSPACE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($created) {
    return $this->set('created', (int) $created);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Add the IDs of the deleted workspaces to the list of workspaces that will
    // be purged on cron.
    $state = \Drupal::state();
    $deleted_workspace_ids = $state->get('workspace.deleted', []);
    unset($entities[static::DEFAULT_WORKSPACE]);
    $deleted_workspace_ids += array_combine(array_keys($entities), array_keys($entities));
    $state->set('workspace.deleted', $deleted_workspace_ids);

    // Trigger a batch purge to allow empty workspaces to be deleted
    // immediately.
    \Drupal::service('workspaces.manager')->purgeDeletedWorkspacesBatch();
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @deprecated The ::getCurrentUserId method is deprecated in 8.6.x and will
   *   be removed before 9.0.0.
   *
   * @return int[]
   *   An array containing the ID of the current user.
   */
  public static function getCurrentUserId() {
    @trigger_error('The ::getCurrentUserId method is deprecated in 8.6.x and will be removed before 9.0.0.', E_USER_DEPRECATED);
    return [\Drupal::currentUser()->id()];
  }

}
