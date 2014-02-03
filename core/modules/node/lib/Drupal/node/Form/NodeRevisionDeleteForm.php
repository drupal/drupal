<?php

/**
 * @file
 * Contains \Drupal\node\Form\NodeRevisionDeleteForm.
 */

namespace Drupal\node\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\node\NodeInterface;
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
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $nodeStorage;

  /**
   * The node type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
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
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $node_type_storage
   *   The node type storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageControllerInterface $node_storage, EntityStorageControllerInterface $node_type_storage, Connection $connection) {
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
      $entity_manager->getStorageController('node'),
      $entity_manager->getStorageController('node_type'),
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
    return t('Are you sure you want to delete the revision from %revision-date?', array('%revision-date' => format_date($this->revision->getRevisionCreationTime())));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
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
  public function buildForm(array $form, array &$form_state, $node_revision = NULL) {
    $this->revision = $this->nodeStorage->loadRevision($node_revision);
    $form = parent::buildForm($form, $form_state);

    // @todo Convert to getCancelRoute() after http://drupal.org/node/1863906.
    $form['actions']['cancel']['#href'] = 'node/' . $this->revision->id() . '/revisions';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->nodeStorage->deleteRevision($this->revision->getRevisionId());

    watchdog('content', '@type: deleted %title revision %revision.', array('@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()));
    $node_type = $this->nodeTypeStorage->load($this->revision->bundle())->label();
    drupal_set_message(t('Revision from %revision-date of @type %title has been deleted.', array('%revision-date' => format_date($this->revision->getRevisionCreationTime()), '@type' => $node_type, '%title' => $this->revision->label())));
    $form_state['redirect_route'] = array(
      'route_name' => 'node.view',
      'route_parameters' => array(
        'node' => $this->revision->id(),
      ),
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {node_field_revision} WHERE nid = :nid', array(':nid' => $this->revision->id()))->fetchField() > 1) {
      $form_state['redirect_route']['route_name'] = 'node.revision_overview';
    }
  }

}
