<?php

/**
 * @file
 * Contains \Drupal\action\Plugin\Action\EmailAction.
 */

namespace Drupal\action\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends an email message.
 *
 * @Action(
 *   id = "action_send_email_action",
 *   label = @Translation("Send email"),
 *   type = "system"
 * )
 */
class EmailAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a EmailAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token, EntityManagerInterface $entity_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
    $this->storage = $entity_manager->getStorage('user');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('token'),
      $container->get('entity.manager'),
      $container->get('logger.factory')->get('action')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (empty($this->configuration['node'])) {
      $this->configuration['node'] = $entity;
    }

    $recipient = $this->token->replace($this->configuration['recipient'], $this->configuration);

    // If the recipient is a registered user with a language preference, use
    // the recipient's preferred language. Otherwise, use the system default
    // language.
    $recipient_accounts = $this->storage->loadByProperties(array('mail' => $recipient));
    $recipient_account = reset($recipient_accounts);
    if ($recipient_account) {
      $langcode = $recipient_account->getPreferredLangcode();
    }
    else {
      $langcode = language_default()->getId();
    }
    $params = array('context' => $this->configuration);

    if (drupal_mail('system', 'action_send_email', $recipient, $langcode, $params)) {
      $this->logger->notice('Sent email to %recipient', array('%recipient' => $recipient));
    }
    else {
      $this->logger->error('Unable to send email to %recipient', array('%recipient' => $recipient));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'recipient' => '',
      'subject' => '',
      'message' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['recipient'] = array(
      '#type' => 'textfield',
      '#title' => t('Recipient'),
      '#default_value' => $this->configuration['recipient'],
      '#maxlength' => '254',
      '#description' => t('The email address to which the message should be sent OR enter [node:author:mail], [comment:author:mail], etc. if you would like to send an email to the author of the original post.'),
    );
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => $this->configuration['subject'],
      '#maxlength' => '254',
      '#description' => t('The subject of the message.'),
    );
    $form['message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#default_value' => $this->configuration['message'],
      '#cols' => '80',
      '#rows' => '20',
      '#description' => t('The message that should be sent. You may include placeholders like [node:title], [user:name], and [comment:body] to represent data that will be different each time message is sent. Not all placeholders will be available in all contexts.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!valid_email_address($form_state->getValue('recipient')) && strpos($form_state->getValue('recipient'), ':mail') === FALSE) {
      // We want the literal %author placeholder to be emphasized in the error message.
      $form_state->setErrorByName('recipient', t('Enter a valid email address or use a token email address such as %author.', array('%author' => '[node:author:mail]')));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['recipient'] = $form_state->getValue('recipient');
    $this->configuration['subject'] = $form_state->getValue('subject');
    $this->configuration['message'] = $form_state->getValue('message');
  }

}
