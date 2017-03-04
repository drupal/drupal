<?php

namespace Drupal\node\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a node revision.
 */
class NodeRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The node revision.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $revision;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The node type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new NodeRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
   *   The node type storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $node_storage, EntityStorageInterface $node_type_storage, Connection $connection) {
    $this->nodeStorage = $node_storage;
    $this->nodeTypeStorage = $node_type_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('node'),
      $entity_manager->getStorage('node_type'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', ['%revision-date' => format_date($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.node.version_history', ['node' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_revision = NULL) {
    $this->revision = $this->nodeStorage->loadRevision($node_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->nodeStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('@type: deleted %title revision %revision.', ['@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $node_type = $this->nodeTypeStorage->load($this->revision->bundle())->label();
    drupal_set_message(t('Revision from %revision-date of @type %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '@type' => $node_type, '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.node.canonical',
      ['node' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {node_field_revision} WHERE nid = :nid', [':nid' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.node.version_history',
        ['node' => $this->revision->id()]
      );
    }
  }

}
