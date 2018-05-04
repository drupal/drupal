<?php

namespace Drupal\workspace\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Drupal\workspace\WorkspaceInterface;

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
 *     "list_builder" = "\Drupal\workspace\WorkspaceListBuilder",
 *     "access" = "Drupal\workspace\WorkspaceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "\Drupal\workspace\Form\WorkspaceForm",
 *       "add" = "\Drupal\workspace\Form\WorkspaceForm",
 *       "edit" = "\Drupal\workspace\Form\WorkspaceForm",
 *       "delete" = "\Drupal\workspace\Form\WorkspaceDeleteForm",
 *       "activate" = "\Drupal\workspace\Form\WorkspaceActivateForm",
 *       "deploy" = "\Drupal\workspace\Form\WorkspaceDeployForm",
 *     },
 *   },
 *   admin_permission = "administer workspaces",
 *   base_table = "workspace",
 *   revision_table = "workspace_revision",
 *   data_table = "workspace_field_data",
 *   revision_data_table = "workspace_field_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/workflow/workspace/add",
 *     "edit-form" = "/admin/config/workflow/workspace/manage/{workspace}/edit",
 *     "delete-form" = "/admin/config/workflow/workspace/manage/{workspace}/delete",
 *     "activate-form" = "/admin/config/workflow/workspace/manage/{workspace}/activate",
 *     "deploy-form" = "/admin/config/workflow/workspace/manage/{workspace}/deploy",
 *     "collection" = "/admin/config/workflow/workspace",
 *   },
 * )
 */
class Workspace extends ContentEntityBase implements WorkspaceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

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

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Owner'))
      ->setDescription(new TranslatableMarkup('The workspace owner.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\workspace\Entity\Workspace::getCurrentUserId')
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
      ->setDescription(new TranslatableMarkup('The time that the workspaces was created.'));

    $fields['target'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Target workspace'))
      ->setDescription(new TranslatableMarkup('The workspace to push to and pull from.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue('live');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function push() {
    return $this->getRepositoryHandler()->push();
  }

  /**
   * {@inheritdoc}
   */
  public function pull() {
    return $this->getRepositoryHandler()->pull();
  }

  /**
   * {@inheritdoc}
   */
  public function getRepositoryHandler() {
    return \Drupal::service('plugin.manager.workspace.repository_handler')->createFromWorkspace($this);
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
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    return $this->set('uid', $account->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    return $this->set('uid', $uid);
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
    \Drupal::service('workspace.manager')->purgeDeletedWorkspacesBatch();
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return int[]
   *   An array containing the ID of the current user.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
