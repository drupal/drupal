<?php

namespace Drupal\contact;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for contact message forms.
 *
 * @internal
 */
class MessageForm extends ContentEntityForm {

  /**
   * The message being used by this form.
   *
   * @var \Drupal\contact\MessageInterface
   */
  protected $entity;

  /**
   * The flood control mechanism.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The contact mail handler service.
   *
   * @var \Drupal\contact\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a MessageForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood control mechanism.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\contact\MailHandlerInterface $mail_handler
   *   The contact mail handler service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, FloodInterface $flood, LanguageManagerInterface $language_manager, MailHandlerInterface $mail_handler, DateFormatterInterface $date_formatter, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->flood = $flood;
    $this->languageManager = $language_manager;
    $this->mailHandler = $mail_handler;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('flood'),
      $container->get('language_manager'),
      $container->get('contact.mail_handler'),
      $container->get('date.formatter'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $message = $this->entity;
    $form = parent::form($form, $form_state, $message);
    $form['#attributes']['class'][] = 'contact-form';

    if (!empty($message->preview)) {
      $form['preview'] = [
        '#theme_wrappers' => ['container__preview'],
        '#attributes' => ['class' => ['preview']],
      ];
      $form['preview']['message'] = $this->entityTypeManager->getViewBuilder('contact_message')->view($message, 'full');
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email address'),
      '#required' => TRUE,
    ];
    if ($user->isAnonymous()) {
      $form['#attached']['library'][] = 'core/drupal.form';
      $form['#attributes']['data-user-info-from-browser'] = TRUE;
    }
    // Do not allow authenticated users to alter the name or email values to
    // prevent the impersonation of other users.
    else {
      $form['name']['#type'] = 'item';
      $form['name']['#value'] = $user->getDisplayName();
      $form['name']['#required'] = FALSE;
      $form['name']['#plain_text'] = $user->getDisplayName();

      $form['mail']['#type'] = 'item';
      $form['mail']['#value'] = $user->getEmail();
      $form['mail']['#required'] = FALSE;
      $form['mail']['#plain_text'] = $user->getEmail();
    }

    // The user contact form has a preset recipient.
    if ($message->isPersonal()) {
      $form['recipient'] = [
        '#type' => 'item',
        '#title' => $this->t('To'),
        '#value' => $message->getPersonalRecipient()->id(),
        'name' => [
          '#theme' => 'username',
          '#account' => $message->getPersonalRecipient(),
        ],
      ];
    }

    $form['copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send yourself a copy'),
      // Do not allow anonymous users to send themselves a copy, because it can
      // be abused to spam people.
      '#access' => $user->isAuthenticated(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = $this->t('Send message');
    $elements['preview'] = [
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      '#submit' => ['::submitForm', '::preview'],
      '#access' => !empty($form_state->getStorage()['form_display']->getComponent('preview')),
    ];
    return $elements;
  }

  /**
   * Form submission handler for the 'preview' action.
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $message = $this->entity;
    $message->preview = TRUE;
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $message = parent::validateForm($form, $form_state);

    // Check if flood control has been activated for sending emails.
    if (!$this->currentUser()->hasPermission('administer contact forms') && (!$message->isPersonal() || !$this->currentUser()->hasPermission('administer users'))) {
      $limit = $this->config('contact.settings')->get('flood.limit');
      $interval = $this->config('contact.settings')->get('flood.interval');

      if (!$this->flood->isAllowed('contact', $limit, $interval)) {
        $form_state->setErrorByName('', $this->t('You cannot send more than %limit messages in @interval. Try again later.', [
          '%limit' => $limit,
          '@interval' => $this->dateFormatter->formatInterval($interval),
        ]));
      }
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $message = $this->entity;
    $user = $this->currentUser();
    // Save the message. In core this is a no-op but should contrib wish to
    // implement message storage, this will make the task of swapping in a real
    // storage controller straight-forward.
    $message->save();
    $this->mailHandler->sendMailMessages($message, $user);
    $contact_form = $message->getContactForm();

    $this->flood->register('contact', $this->config('contact.settings')->get('flood.interval'));
    if ($submission_message = $contact_form->getMessage()) {
      $this->messenger()->addStatus($submission_message);
    }

    // To avoid false error messages caused by flood control, redirect away from
    // the contact form; either to the contacted user account or the front page.
    if ($message->isPersonal() && $user->hasPermission('access user profiles')) {
      $form_state->setRedirectUrl($message->getPersonalRecipient()->toUrl());
    }
    else {
      $form_state->setRedirectUrl($contact_form->getRedirectUrl());
    }
  }

}
