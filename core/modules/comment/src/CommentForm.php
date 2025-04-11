<?php

namespace Drupal\comment;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base handler for comment forms.
 *
 * @internal
 */
class CommentForm extends ContentEntityForm {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('current_user'),
      $container->get('renderer'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Constructs a new CommentForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface|null $entity_field_manager
   *   (optional) The entity field manager service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    AccountInterface $current_user,
    RendererInterface $renderer,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    ?EntityFieldManagerInterface $entity_field_manager = NULL,
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->entityFieldManager = $entity_field_manager ?: \Drupal::service('entity_field.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity;
    $entity = $this->entityTypeManager->getStorage($comment->getCommentedEntityTypeId())->load($comment->getCommentedEntityId());
    $field_name = $comment->getFieldName();
    $field_definition = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$comment->getFieldName()];
    $config = $this->config('user.settings');

    // In several places within this function, we vary $form on:
    // - The current user's permissions.
    // - Whether the current user is authenticated or anonymous.
    // - The 'user.settings' configuration.
    // - The comment field's definition.
    $form['#cache']['contexts'][] = 'user.permissions';
    $form['#cache']['contexts'][] = 'user.roles:authenticated';
    $this->renderer->addCacheableDependency($form, $config);
    $this->renderer->addCacheableDependency($form, $field_definition->getConfig($entity->bundle()));

    // Use #comment-form as unique jump target, regardless of entity type.
    $form['#id'] = Html::getUniqueId('comment_form');
    $form['#theme'] = ['comment_form__' . $entity->getEntityTypeId() . '__' . $entity->bundle() . '__' . $field_name, 'comment_form'];

    $anonymous_contact = $field_definition->getSetting('anonymous');
    $is_admin = $comment->id() && $this->currentUser->hasPermission('administer comments');

    // If not replying to a comment, use our dedicated page callback for new
    // Comments on entities.
    if (!$comment->id() && !$comment->hasParentComment()) {
      $form['#action'] = Url::fromRoute('comment.reply', ['entity_type' => $entity->getEntityTypeId(), 'entity' => $entity->id(), 'field_name' => $field_name])->toString();
    }

    $comment_preview = $form_state->get('comment_preview');
    if (isset($comment_preview)) {
      $form += $comment_preview;
    }

    $form['author'] = [];
    // Display author information in a details element for comment moderators.
    if ($is_admin) {
      $form['author'] += [
        '#type' => 'details',
        '#title' => $this->t('Administration'),
      ];
    }

    // Prepare default values for form elements.
    $author = '';
    if ($is_admin) {
      if (!$comment->getOwnerId()) {
        $author = $comment->getAuthorName();
      }
      $status = $comment->isPublished() ? CommentInterface::PUBLISHED : CommentInterface::NOT_PUBLISHED;
      if (empty($comment_preview)) {
        $form['#title'] = $this->t('Edit comment %title', [
          '%title' => $comment->getSubject(),
        ]);
      }
    }
    else {
      $status = ($this->currentUser->hasPermission('skip comment approval') ? CommentInterface::PUBLISHED : CommentInterface::NOT_PUBLISHED);
    }

    $date = '';
    if ($comment->id()) {
      $date = !empty($comment->date) ? $comment->date : DrupalDateTime::createFromTimestamp($comment->getCreatedTime());
    }

    // The uid field is only displayed when a user with the permission
    // 'administer comments' is editing an existing comment from an
    // authenticated user.
    $owner = $comment->getOwner();
    $form['author']['uid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#default_value' => $owner->isAnonymous() ? NULL : $owner,
      // A comment can be made anonymous by leaving this field empty therefore
      // there is no need to list them in the autocomplete.
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#title' => $this->t('Authored by'),
      '#description' => $this->t('Leave blank for %anonymous.', ['%anonymous' => $config->get('anonymous')]),
      '#access' => $is_admin,
    ];

    // The name field is displayed when an anonymous user is adding a comment or
    // when a user with the permission 'administer comments' is editing an
    // existing comment from an anonymous user.
    $form['author']['name'] = [
      '#type' => 'textfield',
      '#title' => $is_admin ? $this->t('Name for @anonymous', ['@anonymous' => $config->get('anonymous')]) : $this->t('Your name'),
      '#default_value' => $author,
      '#required' => ($this->currentUser->isAnonymous() && $anonymous_contact == CommentInterface::ANONYMOUS_MUST_CONTACT),
      '#maxlength' => 60,
      '#access' => $this->currentUser->isAnonymous() || $is_admin,
      '#size' => 30,
    ];

    if ($is_admin) {
      // When editing a comment only display the name textfield if the uid field
      // is empty.
      $form['author']['name']['#states'] = [
        'visible' => [
          ':input[name="uid"]' => ['empty' => TRUE],
        ],
      ];
    }

    // Add author email and homepage fields depending on the current user.
    $form['author']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $comment->getAuthorEmail(),
      '#required' => ($this->currentUser->isAnonymous() && $anonymous_contact == CommentInterface::ANONYMOUS_MUST_CONTACT),
      '#maxlength' => 64,
      '#size' => 30,
      '#description' => $this->t('The content of this field is kept private and will not be shown publicly.'),
      '#access' => ($comment->getOwner()->isAnonymous() && $is_admin) || ($this->currentUser->isAnonymous() && $anonymous_contact != CommentInterface::ANONYMOUS_MAYNOT_CONTACT),
    ];

    $form['author']['homepage'] = [
      '#type' => 'url',
      '#title' => $this->t('Homepage'),
      '#default_value' => $comment->getHomepage(),
      '#maxlength' => 255,
      '#size' => 30,
      '#access' => $is_admin || ($this->currentUser->isAnonymous() && $anonymous_contact != CommentInterface::ANONYMOUS_MAYNOT_CONTACT),
    ];

    // Add administrative comment publishing options.
    $form['author']['date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Authored on'),
      '#default_value' => $date,
      '#size' => 20,
      '#access' => $is_admin,
    ];

    $form['author']['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#default_value' => $status,
      '#options' => [
        CommentInterface::PUBLISHED => $this->t('Published'),
        CommentInterface::NOT_PUBLISHED => $this->t('Not published'),
      ],
      '#access' => $is_admin,
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity;
    $entity = $comment->getCommentedEntity();
    $field_definition = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$comment->getFieldName()];
    $preview_mode = $field_definition->getSetting('preview');

    // No delete action on the comment form.
    unset($element['delete']);

    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';

    // Only show the save button if comment previews are optional or if we are
    // already previewing the submission.
    $element['submit']['#access'] = ($comment->id() && $this->currentUser->hasPermission('administer comments')) || $preview_mode != DRUPAL_REQUIRED || $form_state->get('comment_preview');

    $element['preview'] = [
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      '#access' => $preview_mode != DRUPAL_DISABLED,
      '#submit' => ['::submitForm', '::preview'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = parent::buildEntity($form, $form_state);
    if (!$form_state->isValueEmpty('date') && $form_state->getValue('date') instanceof DrupalDateTime) {
      $comment->setCreatedTime($form_state->getValue('date')->getTimestamp());
    }
    else {
      $comment->setCreatedTime($this->time->getRequestTime());
    }
    // Empty author ID should revert to anonymous.
    $author_id = $form_state->getValue('uid');
    if ($comment->id() && $this->currentUser->hasPermission('administer comments')) {
      // Admin can leave the author ID blank to revert to anonymous.
      $author_id = $author_id ?: 0;
    }
    if (!is_null($author_id)) {
      if ($author_id === 0 && $form['author']['name']['#access']) {
        // Use the author name value when the form has access to the element and
        // the author ID is anonymous.
        $comment->setAuthorName($form_state->getValue('name'));
      }
      else {
        // Ensure the author name is not set.
        $comment->setAuthorName(NULL);
      }
    }
    else {
      $author_id = $this->currentUser->id();
    }
    $comment->setOwnerId($author_id);

    // Validate the comment's subject. If not specified, extract from comment
    // body.
    if (trim($comment->getSubject()) == '') {
      if ($comment->hasField('comment_body') && !$comment->comment_body->isEmpty()) {
        // The body may be in any format, so:
        // 1) Filter it into HTML
        // 2) Strip out all HTML tags
        // 3) Convert entities back to plain-text.
        $comment_text = $comment->comment_body->processed;
        $comment->setSubject(Unicode::truncate(trim(Html::decodeEntities(strip_tags($comment_text))), 29, TRUE, TRUE));
      }
      // Edge cases where the comment body is populated only by HTML tags will
      // require a default subject.
      if (trim($comment->getSubject()) == '') {
        $comment->setSubject($this->t('(No subject)'));
      }
    }
    return $comment;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_merge(['created', 'name'], parent::getEditedFieldNames($form_state));
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Manually flag violations of fields not handled by the form display.
    foreach ($violations->getByField('created') as $violation) {
      $form_state->setErrorByName('date', $violation->getMessage());
    }
    foreach ($violations->getByField('name') as $violation) {
      $form_state->setErrorByName('name', $violation->getMessage());
    }
    parent::flagViolations($violations, $form, $form_state);
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function preview(array &$form, FormStateInterface $form_state) {
    $comment_preview = comment_preview($this->entity, $form_state);
    $comment_preview['#title'] = $this->t('Preview comment');
    $form_state->set('comment_preview', $comment_preview);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $comment = $this->entity;
    $entity = $comment->getCommentedEntity();
    $is_new = $this->entity->isNew();
    $field_name = $comment->getFieldName();
    $uri = $entity->toUrl();
    $logger = $this->logger('comment');

    if ($this->currentUser->hasPermission('post comments') && ($this->currentUser->hasPermission('administer comments') || $entity->{$field_name}->status == CommentItemInterface::OPEN)) {
      $comment->save();
      $form_state->setValue('cid', $comment->id());

      // Add a log entry.
      $logger->info('Comment posted: %subject.', [
        '%subject' => $comment->getSubject(),
        'link' => Link::fromTextAndUrl($this->t('View'), $comment->toUrl()->setOption('fragment', 'comment-' . $comment->id()))->toString(),
      ]);
      // Add an appropriate message upon submitting the comment form.
      $this->messenger()->addStatus($this->getStatusMessage($comment, $is_new));
      $query = [];
      // Find the current display page for this comment.
      $field_definition = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$field_name];
      $page = $this->entityTypeManager->getStorage('comment')->getDisplayOrdinal($comment, $field_definition->getSetting('default_mode'), $field_definition->getSetting('per_page'));
      if ($page > 0) {
        $query['page'] = $page;
      }
      // Redirect to the newly posted comment.
      $uri->setOption('query', $query);
      $uri->setOption('fragment', 'comment-' . $comment->id());
    }
    else {
      $logger->warning('Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', ['%subject' => $comment->getSubject()]);
      $this->messenger()->addError($this->t('Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', ['%subject' => $comment->getSubject()]));
      // Redirect the user to the entity they are commenting on.
    }
    $form_state->setRedirectUrl($uri);
  }

  /**
   * Gets an appropriate status message when a comment is saved.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment being saved.
   * @param bool $is_new
   *   TRUE if a new comment is created. $comment->isNew() cannot be used here
   *   because the comment has already been saved by the time the message is
   *   rendered.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A translatable string containing the appropriate status message.
   */
  protected function getStatusMessage(CommentInterface $comment, bool $is_new): TranslatableMarkup {
    if (!$comment->isPublished() && !$this->currentUser->hasPermission('administer comments')) {
      return $this->t('Your comment has been queued for review by site administrators and will be published after approval.');
    }
    // Check whether the comment is new or not.
    if ($is_new) {
      return $this->t('Your comment has been posted.');
    }
    return $this->t('Your comment has been updated.');
  }

}
