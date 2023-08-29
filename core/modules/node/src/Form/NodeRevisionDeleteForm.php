<?php

namespace Drupal\node\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a node revision.
 *
 * @internal
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
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new NodeRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
   *   The node type storage.
   * @param \Drupal\Core\Access\AccessManagerInterface|\Drupal\Core\Database\Connection $access_manager
   *   The access manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityStorageInterface $node_storage, EntityStorageInterface $node_type_storage, AccessManagerInterface|Connection $access_manager, DateFormatterInterface $date_formatter) {
    $this->nodeStorage = $node_storage;
    $this->nodeTypeStorage = $node_type_storage;
    $this->accessManager = $access_manager;
    $this->dateFormatter = $date_formatter;
    if ($access_manager instanceof Connection) {
      $this->connection = $access_manager;
      $this->accessManager = func_get_arg(3);
      @trigger_error('Calling ' . __CLASS__ . '::_construct() with the $connection argument is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3343754', E_USER_DEPRECATED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('node'),
      $entity_type_manager->getStorage('node_type'),
      $container->get('access_manager'),
      $container->get('date.formatter')
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
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
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
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node_revision = NULL) {
    $this->revision = $node_revision;
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->nodeStorage;
    $storage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->info('@type: deleted %title revision %revision.', ['@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $node_type = $this->nodeTypeStorage->load($this->revision->bundle())->label();
    $this->messenger()
      ->addStatus($this->t('Revision from %revision-date of @type %title has been deleted.', [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
        '@type' => $node_type,
        '%title' => $this->revision->label(),
      ]));
    // Set redirect to the revisions history page.
    $route_name = 'entity.node.version_history';
    $parameters = ['node' => $this->revision->id()];
    // If no revisions found, or the user does not have access to the revisions
    // page, then redirect to the canonical node page instead.
    if (!$this->accessManager->checkNamedRoute($route_name, $parameters) || count($this->nodeStorage->revisionIds($this->revision)) === 1) {
      $route_name = 'entity.node.canonical';
    }
    $form_state->setRedirect($route_name, $parameters);
  }

}
