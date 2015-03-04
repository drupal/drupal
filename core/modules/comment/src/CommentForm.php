<?php

/**
 * @file
 * Definition of Drupal\comment\CommentForm.
 */

namespace Drupal\comment;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base for controller for comment forms.
 */
class CommentForm extends ContentEntityForm {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a new CommentForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccountInterface $current_user) {
    parent::__construct($entity_manager);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    $comment = $this->entity;

    // Make the comment inherit the current content language unless specifically
    // set.
    if ($comment->isNew()) {
      $language_content = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      $comment->langcode->value = $language_content->getId();
    }

    parent::init($form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity;
    $entity = $this->entityManager->getStorage($comment->getCommentedEntityTypeId())->load($comment->getCommentedEntityId());
    $field_name = $comment->getFieldName();
    $field_definition = $this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$comment->getFieldName()];
    $config = $this->config('user.settings');

    // Use #comment-form as unique jump target, regardless of entity type.
    $form['#id'] = drupal_html_id('comment_form');
    $form['#theme'] = array('comment_form__' . $entity->getEntityTypeId() . '__' . $entity->bundle() . '__' . $field_name, 'comment_form');

    $anonymous_contact = $field_definition->getSetting('anonymous');
    $is_admin = $comment->id() && $this->currentUser->hasPermission('administer comments');

    if (!$this->currentUser->isAuthenticated() && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT) {
      $form['#attached']['library'][] = 'core/drupal.form';
      $form['#attributes']['data-user-info-from-browser'] = TRUE;
    }

    // If not replying to a comment, use our dedicated page callback for new
    // Comments on entities.
    if (!$comment->id() && !$comment->hasParentComment()) {
      $form['#action'] = $this->url('comment.reply', array('entity_type' => $entity->getEntityTypeId(), 'entity' => $entity->id(), 'field_name' => $field_name));
    }

    $comment_preview = $form_state->get('comment_preview');
    if (isset($comment_preview)) {
      $form += $comment_preview;
    }

    $form['author'] = array();
    // Display author information in a details element for comment moderators.
    if ($is_admin) {
      $form['author'] += array(
        '#type' => 'details',
        '#title' => $this->t('Administration'),
      );
    }

    // Prepare default values for form elements.
    if ($is_admin) {
      $author = $comment->getAuthorName();
      $status = $comment->getStatus();
      if (empty($comment_preview)) {
        $form['#title'] = $this->t('Edit comment %title', array(
          '%title' => $comment->getSubject(),
        ));
      }
    }
    else {
      if ($this->currentUser->isAuthenticated()) {
        $author = $this->currentUser->getUsername();
      }
      else {
        $author = ($comment->getAuthorName() ? $comment->getAuthorName() : '');
      }
      $status = ($this->currentUser->hasPermission('skip comment approval') ? CommentInterface::PUBLISHED : CommentInterface::NOT_PUBLISHED);
    }

    $date = '';
    if ($comment->id()) {
      $date = !empty($comment->date) ? $comment->date : DrupalDateTime::createFromTimestamp($comment->getCreatedTime());
    }

    // Add the author name field depending on the current user.
    $form['author']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#default_value' => $author,
      '#required' => ($this->currentUser->isAnonymous() && $anonymous_contact == COMMENT_ANONYMOUS_MUST_CONTACT),
      '#maxlength' => 60,
      '#size' => 30,
    );
    if ($is_admin) {
      $form['author']['name']['#title'] = $this->t('Authored by');
      $form['author']['name']['#description'] = $this->t('Leave blank for %anonymous.', array('%anonymous' => $config->get('anonymous')));
      $form['author']['name']['#autocomplete_route_name'] = 'user.autocomplete';
    }
    elseif ($this->currentUser->isAuthenticated()) {
      $form['author']['name']['#type'] = 'item';
      $form['author']['name']['#value'] = $form['author']['name']['#default_value'];
      $form['author']['name']['#theme'] = 'username';
      $form['author']['name']['#account'] = $this->currentUser;
    }
    elseif($this->currentUser->isAnonymous()) {
      $form['author']['name']['#attributes']['data-drupal-default-value'] = $config->get('anonymous');
    }

    // Add author email and homepage fields depending on the current user.
    $form['author']['mail'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $comment->getAuthorEmail(),
      '#required' => ($this->currentUser->isAnonymous() && $anonymous_contact == COMMENT_ANONYMOUS_MUST_CONTACT),
      '#maxlength' => 64,
      '#size' => 30,
      '#description' => $this->t('The content of this field is kept private and will not be shown publicly.'),
      '#access' => ($comment->getOwner()->isAnonymous() && $is_admin) || ($this->currentUser->isAnonymous() && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT),
    );

    $form['author']['homepage'] = array(
      '#type' => 'url',
      '#title' => $this->t('Homepage'),
      '#default_value' => $comment->getHomepage(),
      '#maxlength' => 255,
      '#size' => 30,
      '#access' => $is_admin || ($this->currentUser->isAnonymous() && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT),
    );

    // Add administrative comment publishing options.
    $form['author']['date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Authored on'),
      '#default_value' => $date,
      '#size' => 20,
      '#access' => $is_admin,
    );

    $form['author']['status'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#default_value' => $status,
      '#options' => array(
        CommentInterface::PUBLISHED => $this->t('Published'),
        CommentInterface::NOT_PUBLISHED => $this->t('Not published'),
      ),
      '#access' => $is_admin,
    );

    $form['#cache']['tags'] = Cache::mergeTags(isset($form['#cache']['tags']) ? $form['#cache']['tags'] : [],  $config->getCacheTags());

    return parent::form($form, $form_state, $comment);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::actions().
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    /* @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity;
    $entity = $comment->getCommentedEntity();
    $field_definition = $this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$comment->getFieldName()];
    $preview_mode = $field_definition->getSetting('preview');

    // No delete action on the comment form.
    unset($element['delete']);

    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';

    // Only show the save button if comment previews are optional or if we are
    // already previewing the submission.
    $element['submit']['#access'] = ($comment->id() && $this->currentUser->hasPermission('administer comments')) || $preview_mode != DRUPAL_REQUIRED || $form_state->get('comment_preview');

    $element['preview'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      '#access' => $preview_mode != DRUPAL_DISABLED,
      '#validate' => array('::validate'),
      '#submit' => array('::submitForm', '::preview'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = parent::buildEntity($form, $form_state);
    if (!$form_state->isValueEmpty('date') && $form_state->getValue('date') instanceOf DrupalDateTime) {
      $comment->setCreatedTime($form_state->getValue('date')->getTimestamp());
    }
    else {
      $comment->setCreatedTime(REQUEST_TIME);
    }
    $author_name = $form_state->getValue('name');

    if (!$this->currentUser->isAnonymous()) {
      // Assign the owner based on the given user name - none means anonymous.
      $accounts = $this->entityManager->getStorage('user')
        ->loadByProperties(array('name' => $author_name));
      $account = reset($accounts);
      $uid = $account ? $account->id() : 0;
      $comment->setOwnerId($uid);
    }

    // If the comment was posted by an anonymous user and no author name was
    // required, use "Anonymous" by default.
    if ($comment->getOwnerId() === 0 && (!isset($author_name) || $author_name === '')) {
      $comment->setAuthorName($this->config('user.settings')->get('anonymous'));
    }

    // Validate the comment's subject. If not specified, extract from comment
    // body.
    if (trim($comment->getSubject()) == '') {
      // The body may be in any format, so:
      // 1) Filter it into HTML
      // 2) Strip out all HTML tags
      // 3) Convert entities back to plain-text.
      $comment_text = $comment->comment_body->processed;
      $comment->setSubject(Unicode::truncate(trim(String::decodeEntities(strip_tags($comment_text))), 29, TRUE));
      // Edge cases where the comment body is populated only by HTML tags will
      // require a default subject.
      if ($comment->getSubject() == '') {
        $comment->setSubject($this->t('(No subject)'));
      }
    }
    return $comment;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $comment = parent::validate($form, $form_state);

    // Customly trigger validation of manually added fields and add in
    // violations.
    $violations = $comment->created->validate();
    foreach ($violations as $violation) {
      $form_state->setErrorByName('date', $violation->getMessage());
    }
    $violations = $comment->name->validate();
    foreach ($violations as $violation) {
      $form_state->setErrorByName('name', $violation->getMessage());
    }

    return $comment;
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function preview(array &$form, FormStateInterface $form_state) {
    $comment_preview = comment_preview($this->entity, $form_state);
    $comment_preview['#title'] = $this->t('Preview comment');
    $form_state->set('comment_preview', $comment_preview);
    $form_state->setRebuild();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $comment = $this->entity;
    $entity = $comment->getCommentedEntity();
    $field_name = $comment->getFieldName();
    $uri = $entity->urlInfo();
    $logger = $this->logger('content');

    if ($this->currentUser->hasPermission('post comments') && ($this->currentUser->hasPermission('administer comments') || $entity->{$field_name}->status == CommentItemInterface::OPEN)) {
      $comment->save();
      $form_state->setValue('cid', $comment->id());

      // Add a log entry.
      $logger->notice('Comment posted: %subject.', array(
          '%subject' => $comment->getSubject(),
          'link' => $this->l(t('View'), $comment->urlInfo()->setOption('fragment', 'comment-' . $comment->id()))
        ));

      // Explain the approval queue if necessary.
      if (!$comment->isPublished()) {
        if (!$this->currentUser->hasPermission('administer comments')) {
          drupal_set_message($this->t('Your comment has been queued for review by site administrators and will be published after approval.'));
        }
      }
      else {
        drupal_set_message($this->t('Your comment has been posted.'));
      }
      $query = array();
      // Find the current display page for this comment.
      $field_definition = $this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$field_name];
      $page = $this->entityManager->getStorage('comment')->getDisplayOrdinal($comment, $field_definition->getSetting('default_mode'), $field_definition->getSetting('per_page'));
      if ($page > 0) {
        $query['page'] = $page;
      }
      // Redirect to the newly posted comment.
      $uri->setOption('query', $query);
      $uri->setOption('fragment', 'comment-' . $comment->id());
    }
    else {
      $logger->warning('Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', array('%subject' => $comment->getSubject()));
      drupal_set_message($this->t('Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', array('%subject' => $comment->getSubject())), 'error');
      // Redirect the user to the entity they are commenting on.
    }
    $form_state->setRedirectUrl($uri);
  }
}
