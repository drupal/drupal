<?php

namespace Drupal\node\Entity;

use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Form\NodeTypeDeleteConfirm;
use Drupal\node\NodeTypeAccessControlHandler;
use Drupal\node\NodeTypeForm;
use Drupal\node\NodeTypeInterface;
use Drupal\node\NodeTypeListBuilder;
use Drupal\user\Entity\EntityPermissionsRouteProvider;

/**
 * Defines the Node type configuration entity.
 */
#[ConfigEntityType(
  id: 'node_type',
  label: new TranslatableMarkup('Content type'),
  label_collection: new TranslatableMarkup('Content types'),
  label_singular: new TranslatableMarkup('content type'),
  label_plural: new TranslatableMarkup('content types'),
  config_prefix: 'type',
  entity_keys: [
    'id' => 'type',
    'label' => 'name',
  ],
  handlers: [
    'access' => NodeTypeAccessControlHandler::class,
    'form' => [
      'add' => NodeTypeForm::class,
      'edit' => NodeTypeForm::class,
      'delete' => NodeTypeDeleteConfirm::class,
    ],
    'route_provider' => [
      'permissions' => EntityPermissionsRouteProvider::class,
    ],
    'list_builder' => NodeTypeListBuilder::class,
  ],
  links: [
    'edit-form' => '/admin/structure/types/manage/{node_type}',
    'delete-form' => '/admin/structure/types/manage/{node_type}/delete',
    'entity-permissions-form' => '/admin/structure/types/manage/{node_type}/permissions',
    'collection' => '/admin/structure/types',
  ],
  admin_permission: 'administer content types',
  bundle_of: 'node',
  label_count: [
    'singular' => '@count content type',
    'plural' => '@count content types',
  ],
  config_export: [
    'name',
    'type',
    'description',
    'help',
    'new_revision',
    'preview_mode',
    'display_submitted',
  ],
)]
class NodeType extends ConfigEntityBundleBase implements NodeTypeInterface {

  /**
   * The machine name of this node type.
   *
   * @var string
   *
   * @todo Rename to $id.
   */
  protected $type;

  /**
   * The human-readable name of the node type.
   *
   * @var string
   *
   * @todo Rename to $label.
   */
  protected $name;

  /**
   * A brief description of this node type.
   *
   * @var string|null
   */
  protected $description = NULL;

  /**
   * Help information shown to the user when creating a Node of this type.
   *
   * @var string|null
   */
  protected $help = NULL;

  /**
   * Default value of the 'Create new revision' checkbox of this node type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * The preview mode.
   *
   * @var int
   */
  protected $preview_mode = DRUPAL_OPTIONAL;

  /**
   * Display setting for author and date Submitted by post information.
   *
   * @var bool
   */
  protected $display_submitted = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('node.type.locked');
    return $locked[$this->id()] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Automatically create new revisions'), pluralize: FALSE)]
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function displaySubmitted() {
    return $this->display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set whether to display submission information'), pluralize: FALSE)]
  public function setDisplaySubmitted($display_submitted) {
    $this->display_submitted = $display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewMode() {
    return $this->preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set preview mode'), pluralize: FALSE)]
  public function setPreviewMode($preview_mode) {
    $this->preview_mode = $preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id()) {
      $update_count = $storage->updateType($this->getOriginalId(), $this->id());
      if ($update_count) {
        \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural($update_count,
          'Changed the content type of 1 post from %old-type to %type.',
          'Changed the content type of @count posts from %old-type to %type.',
          [
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          ]));
      }
    }
    if ($update) {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the node type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

}
