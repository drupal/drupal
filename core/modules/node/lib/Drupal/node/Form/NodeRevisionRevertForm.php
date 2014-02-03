<?php

/**
 * @file
 * Contains \Drupal\node\Form\NodeRevisionDeleteForm.
 */

namespace Drupal\node\Form;

use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a node revision.
 */
class NodeRevisionRevertForm extends ConfirmFormBase {

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
   * Constructs a new NodeRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $node_storage
   *   The node storage.
   */
  public function __construct(EntityStorageControllerInterface $node_storage) {
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorageController('node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to revert to the revision from %revision-date?', array('%revision-date' => format_date($this->revision->getRevisionCreationTime())));
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
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
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
    $this->revision->setNewRevision();
    // Make this the new default revision for the node.
    $this->revision->isDefaultRevision(TRUE);

    // The revision timestamp will be updated when the revision is saved. Keep the
    // original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision->log = t('Copy of the revision from %date.', array('%date' => format_date($original_revision_timestamp)));

    $this->revision->save();

    watchdog('content', '@type: reverted %title revision %revision.', array('@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()));
    drupal_set_message(t('@type %title has been reverted back to the revision from %revision-date.', array('@type' => node_get_type_label($this->revision), '%title' => $this->revision->label(), '%revision-date' => format_date($original_revision_timestamp))));
    $form_state['redirect_route'] = array(
      'route_name' => 'node.revision_overview',
      'route_parameters' => array(
        'node' => $this->revision->id(),
      ),
    );
  }

}
