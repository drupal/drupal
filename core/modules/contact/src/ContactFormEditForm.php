<?php

namespace Drupal\contact;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Render\Element\PathElement;

/**
 * Base form for contact form edit forms.
 *
 * @internal
 */
class ContactFormEditForm extends EntityForm implements ContainerInjectionInterface {
  use ConfigFormBaseTrait;

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a new ContactFormEditForm.
   *
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   */
  public function __construct(EmailValidatorInterface $email_validator, PathValidatorInterface $path_validator) {
    $this->emailValidator = $email_validator;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['contact.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $contact_form = $this->entity;
    $default_form = $this->config('contact.settings')->get('default_form');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $contact_form->label(),
      '#description' => $this->t("Example: 'website feedback' or 'product information'."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $contact_form->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => '\Drupal\contact\Entity\ContactForm::load',
      ],
      '#disabled' => !$contact_form->isNew(),
    ];
    $form['recipients'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Recipients'),
      '#default_value' => implode(', ', $contact_form->getRecipients()),
      '#description' => $this->t("Example: 'webmaster@example.com' or 'sales@example.com,support@example.com' . To specify multiple recipients, separate each email address with a comma."),
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => $contact_form->getMessage(),
      '#description' => $this->t('The message to display to the user after submission of this form. Leave blank for no message.'),
    ];
    $form['redirect'] = [
      '#type' => 'path',
      '#title' => $this->t('Redirect path'),
      '#convert_path' => PathElement::CONVERT_NONE,
      '#default_value' => $contact_form->getRedirectPath(),
      '#description' => $this->t('Path to redirect the user to after submission of this form. For example, type "/about" to redirect to that page. Use a relative path with a slash in front.'),
    ];
    $form['reply'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Auto-reply'),
      '#default_value' => $contact_form->getReply(),
      '#description' => $this->t('Optional auto-reply. Leave empty if you do not want to send the user an auto-reply message.'),
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $contact_form->getWeight(),
      '#description' => $this->t('When listing forms, those with lighter (smaller) weights get listed before forms with heavier (larger) weights. Forms with equal weights are sorted alphabetically.'),
    ];
    $form['selected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make this the default form'),
      '#default_value' => $default_form === $contact_form->id(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate and each email recipient.
    $recipients = explode(',', $form_state->getValue('recipients'));

    foreach ($recipients as &$recipient) {
      $recipient = trim($recipient);
      if (!$this->emailValidator->isValid($recipient)) {
        $form_state->setErrorByName('recipients', $this->t('%recipient is an invalid email address.', ['%recipient' => $recipient]));
      }
    }
    $form_state->setValue('recipients', $recipients);
    $redirect_url = $form_state->getValue('redirect');
    if ($redirect_url && $this->pathValidator->isValid($redirect_url)) {
      if (mb_substr($redirect_url, 0, 1) !== '/') {
        $form_state->setErrorByName('redirect', $this->t('The path should start with /.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $contact_form = $this->entity;
    $status = $contact_form->save();
    $contact_settings = $this->config('contact.settings');

    $edit_link = $this->entity->toLink($this->t('Edit'))->toString();
    $view_link = $contact_form->toLink($contact_form->label(), 'canonical')->toString();
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Contact form %label has been updated.', ['%label' => $view_link]));
      $this->logger('contact')->notice('Contact form %label has been updated.', ['%label' => $contact_form->label(), 'link' => $edit_link]);
    }
    else {
      $this->messenger()->addStatus($this->t('Contact form %label has been added.', ['%label' => $view_link]));
      $this->logger('contact')->notice('Contact form %label has been added.', ['%label' => $contact_form->label(), 'link' => $edit_link]);
    }

    // Update the default form.
    if ($form_state->getValue('selected')) {
      $contact_settings
        ->set('default_form', $contact_form->id())
        ->save();
    }
    // If it was the default form, empty out the setting.
    elseif ($contact_settings->get('default_form') == $contact_form->id()) {
      $contact_settings
        ->set('default_form', NULL)
        ->save();
    }

    $form_state->setRedirectUrl($contact_form->toUrl('collection'));
  }

}
