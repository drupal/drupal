<?php

/**
 * @file
 * Contains \Drupal\action\Plugin\Action\EmailAction.
 */

namespace Drupal\action\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends an e-mail message.
 *
 * @Action(
 *   id = "action_send_email_action",
 *   label = @Translation("Send e-mail"),
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
   * The user storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * Constructs a EmailAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Token $token, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
    $this->storageController = $entity_manager->getStorageController('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('token'),
      $container->get('entity.manager')
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
    $recipient_accounts = $this->storageController->loadByProperties(array('mail' => $recipient));
    $recipient_account = reset($recipient_accounts);
    if ($recipient_account) {
      $langcode = $recipient_account->getPreferredLangcode();
    }
    else {
      $langcode = language_default()->id;
    }
    $params = array('context' => $this->configuration);

    if (drupal_mail('system', 'action_send_email', $recipient, $langcode, $params)) {
      watchdog('action', 'Sent email to %recipient', array('%recipient' => $recipient));
    }
    else {
      watchdog('error', 'Unable to send email to %recipient', array('%recipient' => $recipient));
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
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form['recipient'] = array(
      '#type' => 'textfield',
      '#title' => t('Recipient'),
      '#default_value' => $this->configuration['recipient'],
      '#maxlength' => '254',
      '#description' => t('The e-mail address to which the message should be sent OR enter [node:author:mail], [comment:author:mail], etc. if you would like to send an e-mail to the author of the original post.'),
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
  public function validateConfigurationForm(array &$form, array &$form_state) {
    if (!valid_email_address($form_state['values']['recipient']) && strpos($form_state['values']['recipient'], ':mail') === FALSE) {
      // We want the literal %author placeholder to be emphasized in the error message.
      form_set_error('recipient', $form_state, t('Enter a valid email address or use a token e-mail address such as %author.', array('%author' => '[node:author:mail]')));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['recipient'] = $form_state['values']['recipient'];
    $this->configuration['subject'] = $form_state['values']['subject'];
    $this->configuration['message'] = $form_state['values']['message'];
  }

}
