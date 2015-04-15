<?php

/**
 * @file
 * Definition of Drupal\node\NodeForm.
 */

namespace Drupal\node;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the node edit forms.
 */
class NodeForm extends ContentEntityForm {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Whether this node has been previewed or not.
   */
  protected $hasBeenPreviewed = FALSE;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(EntityManagerInterface $entity_manager, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($entity_manager);
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entity;

    if (!$node->isNew()) {
      // Remove the revision log message from the original node entity.
      $node->revision_log = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Try to restore from temp store, this must be done before calling
    // parent::form().
    $uuid = $this->entity->uuid();
    $store = $this->tempStoreFactory->get('node_preview');

    // If the user is creating a new node, the UUID is passed in the request.
    if ($request_uuid = \Drupal::request()->query->get('uuid')) {
      $uuid = $request_uuid;
    }

    if ($preview = $store->get($uuid)) {
      /** @var $preview \Drupal\Core\Form\FormStateInterface */
      $form_state = $preview;

      // Rebuild the form.
      $form_state->setRebuild();
      $this->entity = $preview->getFormObject()->getEntity();
      unset($this->entity->in_preview);

      // Remove the stale temp store entry for existing nodes.
      if (!$this->entity->isNew()) {
        $store->delete($uuid);
      }

      $this->hasBeenPreviewed = TRUE;
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @type</em> @title', array('@type' => node_get_type_label($node), '@title' => $node->label()));
    }

    $current_user = $this->currentUser();

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = array(
      '#type' => 'hidden',
      '#default_value' => $node->getChangedTime(),
    );

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#attributes' => array('class' => array('entity-meta')),
      '#weight' => 99,
    );
    $form = parent::form($form, $form_state);

    // Add a revision_log field if the "Create new revision" option is checked,
    // or if the current user has the ability to check that option.
    $form['revision_information'] = array(
      '#type' => 'details',
      '#group' => 'advanced',
      '#title' => t('Revision information'),
      // Open by default when "Create new revision" is checked.
      '#open' => $node->isNewRevision(),
      '#attributes' => array(
        'class' => array('node-form-revision-information'),
      ),
      '#attached' => array(
        'library' => array('node/drupal.node'),
      ),
      '#weight' => 20,
      '#optional' => TRUE,
    );

    $form['revision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $node->type->entity->isNewRevision(),
      '#access' => $current_user->hasPermission('administer nodes'),
      '#group' => 'revision_information',
    );

    $form['revision_log'] += array(
      '#states' => array(
        'visible' => array(
          ':input[name="revision"]' => array('checked' => TRUE),
        ),
      ),
      '#group' => 'revision_information',
    );

    // Node author information for administrators.
    $form['author'] = array(
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('node-form-author'),
      ),
      '#attached' => array(
        'library' => array('node/drupal.node'),
      ),
      '#weight' => 90,
      '#optional' => TRUE,
    );

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    // Node options for administrators.
    $form['options'] = array(
      '#type' => 'details',
      '#title' => t('Promotion options'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('node-form-options'),
      ),
      '#attached' => array(
        'library' => array('node/drupal.node'),
      ),
      '#weight' => 95,
      '#optional' => TRUE,
    );

    if (isset($form['promote'])) {
      $form['promote']['#group'] = 'options';
    }

    if (isset($form['sticky'])) {
      $form['sticky']['#group'] = 'options';
    }

    $form['#attached']['library'][] = 'node/form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $node = $this->entity;
    $preview_mode = $node->type->entity->getPreviewMode();

    $element['submit']['#access'] = $preview_mode != DRUPAL_REQUIRED || $this->hasBeenPreviewed;

    // If saving is an option, privileged users get dedicated form submit
    // buttons to adjust the publishing status while saving in one go.
    // @todo This adjustment makes it close to impossible for contributed
    //   modules to integrate with "the Save operation" of this form. Modules
    //   need a way to plug themselves into 1) the ::submit() step, and
    //   2) the ::save() step, both decoupled from the pressed form button.
    if ($element['submit']['#access'] && \Drupal::currentUser()->hasPermission('administer nodes')) {
      // isNew | prev status » default   & publish label             & unpublish label
      // 1     | 1           » publish   & Save and publish          & Save as unpublished
      // 1     | 0           » unpublish & Save and publish          & Save as unpublished
      // 0     | 1           » publish   & Save and keep published   & Save and unpublish
      // 0     | 0           » unpublish & Save and keep unpublished & Save and publish

      // Add a "Publish" button.
      $element['publish'] = $element['submit'];
      $element['publish']['#dropbutton'] = 'save';
      if ($node->isNew()) {
        $element['publish']['#value'] = t('Save and publish');
      }
      else {
        $element['publish']['#value'] = $node->isPublished() ? t('Save and keep published') : t('Save and publish');
      }
      $element['publish']['#weight'] = 0;
      array_unshift($element['publish']['#submit'], '::publish');

      // Add a "Unpublish" button.
      $element['unpublish'] = $element['submit'];
      $element['unpublish']['#dropbutton'] = 'save';
      if ($node->isNew()) {
        $element['unpublish']['#value'] = t('Save as unpublished');
      }
      else {
        $element['unpublish']['#value'] = !$node->isPublished() ? t('Save and keep unpublished') : t('Save and unpublish');
      }
      $element['unpublish']['#weight'] = 10;
      array_unshift($element['unpublish']['#submit'], '::unpublish');

      // If already published, the 'publish' button is primary.
      if ($node->isPublished()) {
        unset($element['unpublish']['#button_type']);
      }
      // Otherwise, the 'unpublish' button is primary and should come first.
      else {
        unset($element['publish']['#button_type']);
        $element['unpublish']['#weight'] = -10;
      }

      // Remove the "Save" button.
      $element['submit']['#access'] = FALSE;
    }

    $element['preview'] = array(
      '#type' => 'submit',
      '#access' => $preview_mode != DRUPAL_DISABLED && ($node->access('create') || $node->access('update')),
      '#value' => t('Preview'),
      '#weight' => 20,
      '#validate' => array('::validate'),
      '#submit' => array('::submitForm', '::preview'),
    );

    $element['delete']['#access'] = $node->access('delete');
    $element['delete']['#weight'] = 100;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $node = parent::validate($form, $form_state);

    if ($node->id() && (node_last_changed($node->id(), $this->getFormLangcode($form_state)) > $node->getChangedTime())) {
      $form_state->setErrorByName('changed', $this->t('The content on this page has either been modified by another user, or you have already submitted modifications using this form. As a result, your changes cannot be saved.'));
    }

    return $node;
  }

  /**
   * {@inheritdoc}
   *
   * Updates the node object by processing the submitted values.
   *
   * This function can be called by a "Next" button of a wizard to update the
   * form state's entity with the current step's values before proceeding to the
   * next step.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the node object from the submitted values.
    parent::submitForm($form, $form_state);
    $node = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision') && $form_state->getValue('revision') != FALSE) {
      $node->setNewRevision();
      // If a new revision is created, save the current user as revision author.
      $node->setRevisionCreationTime(REQUEST_TIME);
      $node->setRevisionAuthorId(\Drupal::currentUser()->id());
    }
    else {
      $node->setNewRevision(FALSE);
    }
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $store = $this->tempStoreFactory->get('node_preview');
    $this->entity->in_preview = TRUE;
    $store->set($this->entity->uuid(), $form_state);
    $form_state->setRedirect('entity.node.preview', array(
      'node_preview' => $this->entity->uuid(),
      'view_mode_id' => 'default',
    ));
  }

  /**
   * Form submission handler for the 'publish' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function publish(array $form, FormStateInterface $form_state) {
    $node = $this->entity;
    $node->setPublished(TRUE);
    return $node;
  }

  /**
   * Form submission handler for the 'unpublish' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function unpublish(array $form, FormStateInterface $form_state) {
    $node = $this->entity;
    $node->setPublished(FALSE);
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    // A user might assign the node author by entering a user name in the node
    // form, which we then need to translate to a user ID.
    // @todo: Remove it when https://www.drupal.org/node/2322525 is pushed.
    if (!empty($form_state->getValue('uid')[0]['target_id']) && $account = User::load($form_state->getValue('uid')[0]['target_id'])) {
      $entity->setOwnerId($account->id());
    }
    else {
      $entity->setOwnerId(0);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $node = $this->entity;
    $insert = $node->isNew();
    $node->save();
    $node_link = $node->link($this->t('View'));
    $context = array('@type' => $node->getType(), '%title' => $node->label(), 'link' => $node_link);
    $t_args = array('@type' => node_get_type_label($node), '%title' => $node->label());

    if ($insert) {
      $this->logger('content')->notice('@type: added %title.', $context);
      drupal_set_message(t('@type %title has been created.', $t_args));
    }
    else {
      $this->logger('content')->notice('@type: updated %title.', $context);
      drupal_set_message(t('@type %title has been updated.', $t_args));
    }

    if ($node->id()) {
      $form_state->setValue('nid', $node->id());
      $form_state->set('nid', $node->id());
      if ($node->access('view')) {
        $form_state->setRedirect(
          'entity.node.canonical',
          array('node' => $node->id())
        );
      }
      else {
        $form_state->setRedirect('<front>');
      }

      // Remove the preview entry from the temp store, if any.
      $store = $this->tempStoreFactory->get('node_preview');
      $store->delete($node->uuid());
    }
    else {
      // In the unlikely case something went wrong on save, the node will be
      // rebuilt and node form redisplayed the same way as in preview.
      drupal_set_message(t('The post could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
