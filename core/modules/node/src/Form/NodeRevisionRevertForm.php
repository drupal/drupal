<?php

/**
 * @file
 * Contains \Drupal\node\Form\NodeRevisionRevertForm.
 */

namespace Drupal\node\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new NodeRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(EntityStorageInterface $node_storage) {
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('node')
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
  public function getCancelUrl() {
    return new Url('entity.node.version_history', array('node' => $this->revision->id()));
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
  public function buildForm(array $form, FormStateInterface $form_state, $node_revision = NULL) {
    $this->revision = $this->nodeStorage->loadRevision($node_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $revision = $this->prepareRevertedRevision($this->revision);

    // The revision timestamp will be updated when the revision is saved. Keep the
    // original one for the confirmation message.
    $original_revision_timestamp = $revision->getRevisionCreationTime();
    $revision->revision_log = t('Copy of the revision from %date.', array('%date' => format_date($original_revision_timestamp)));

    $revision->save();

    $this->logger('content')->notice('@type: reverted %title revision %revision.', array('@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()));
    drupal_set_message(t('@type %title has been reverted to the revision from %revision-date.', array('@type' => node_get_type_label($this->revision), '%title' => $this->revision->label(), '%revision-date' => format_date($original_revision_timestamp))));
    $form_state->setRedirect(
      'entity.node.version_history',
      array('node' => $this->revision->id())
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\node\NodeInterface $revision
   *   The revision to be reverted.
   *
   * @return \Drupal\node\NodeInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(NodeInterface $revision) {
    /** @var \Drupal\node\NodeInterface $default_revision */
    $default_revision = $this->nodeStorage->load($revision->id());

    // If the entity is translated, make sure only translations affected by the
    // specified revision are reverted.
    $languages = $default_revision->getTranslationLanguages();
    if (count($languages) > 1) {
      // @todo Instead of processing all the available translations, we should
      //   let the user decide which translations should be reverted. See
      //   https://www.drupal.org/node/2465907.
      foreach ($languages as $langcode => $language) {
        if ($revision->hasTranslation($langcode) && !$revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
          $revision_translation = $revision->getTranslation($langcode);
          $default_translation = $default_revision->getTranslation($langcode);
          foreach ($default_revision->getFieldDefinitions() as $field_name => $definition) {
            if ($definition->isTranslatable()) {
              $revision_translation->set($field_name, $default_translation->get($field_name)->getValue());
            }
          }
        }
      }
    }

    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
