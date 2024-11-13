<?php

namespace Drupal\contact\Entity;

use Drupal\contact\ContactFormAccessControlHandler;
use Drupal\contact\ContactFormEditForm;
use Drupal\contact\ContactFormInterface;
use Drupal\contact\ContactFormListBuilder;
use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\Entity\EntityPermissionsRouteProvider;

/**
 * Defines the contact form entity.
 */
#[ConfigEntityType(
  id: 'contact_form',
  label: new TranslatableMarkup('Contact form'),
  label_collection: new TranslatableMarkup('Contact forms'),
  label_singular: new TranslatableMarkup('contact form'),
  label_plural: new TranslatableMarkup('contact forms'),
  config_prefix: 'form',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: [
    'access' => ContactFormAccessControlHandler::class,
    'list_builder' => ContactFormListBuilder::class,
    'form' => [
      'add' => ContactFormEditForm::class,
      'edit' => ContactFormEditForm::class,
      'delete' => EntityDeleteForm::class,
    ],
    'route_provider' => ['permissions' => EntityPermissionsRouteProvider::class],
  ],
  links: [
    'delete-form' => '/admin/structure/contact/manage/{contact_form}/delete',
    'edit-form' => '/admin/structure/contact/manage/{contact_form}',
    'entity-permissions-form' => '/admin/structure/contact/manage/{contact_form}/permissions',
    'collection' => '/admin/structure/contact',
    'canonical' => '/contact/{contact_form}',
  ],
  admin_permission: 'administer contact forms',
  bundle_of: 'contact_message',
  label_count: [
    'singular' => '@count contact form',
    'plural' => '@count contact forms',
  ],
  config_export: [
    'id',
    'label',
    'recipients',
    'reply',
    'weight',
    'message',
    'redirect',
  ],
)]
class ContactForm extends ConfigEntityBundleBase implements ContactFormInterface {

  /**
   * The form ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label of the category.
   *
   * @var string
   */
  protected $label;

  /**
   * The message displayed to user on form submission.
   *
   * @var string
   */
  protected $message;

  /**
   * List of recipient email addresses.
   *
   * @var array
   */
  protected $recipients = [];

  /**
   * The path to redirect to on form submission.
   *
   * @var string
   */
  protected $redirect;

  /**
   * An auto-reply message.
   *
   * @var string
   */
  protected $reply = '';

  /**
   * The weight of the category.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set contact form message'), pluralize: FALSE)]
  public function setMessage($message) {
    $this->message = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients() {
    return $this->recipients;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set recipients'), pluralize: FALSE)]
  public function setRecipients($recipients) {
    $this->recipients = $recipients;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectPath() {
    return $this->redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    if ($this->redirect) {
      $url = Url::fromUserInput($this->redirect);
    }
    else {
      $url = Url::fromRoute('<front>');
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set redirect path'), pluralize: FALSE)]
  public function setRedirectPath($redirect) {
    $this->redirect = $redirect;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReply() {
    return $this->reply;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set auto-reply message'), pluralize: FALSE)]
  public function setReply($reply) {
    $this->reply = $reply;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Set weight'), pluralize: FALSE)]
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

}
