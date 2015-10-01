<?php

/**
 * @file
 * Contains \Drupal\action\Plugin\Action\MessageAction.
 */

namespace Drupal\action\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends a message to the current user's screen.
 *
 * @Action(
 *   id = "action_message_action",
 *   label = @Translation("Display a message to the user"),
 *   type = "system"
 * )
 */
class MessageAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a MessageAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token
   *   The token service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('token'), $container->get('renderer'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (empty($this->configuration['node'])) {
      $this->configuration['node'] = $entity;
    }
    $message = $this->token->replace($this->configuration['message'], $this->configuration);
    $build = [
      '#markup' => $message,
    ];

    // @todo Fix in https://www.drupal.org/node/2577827
    drupal_set_message($this->renderer->renderPlain($build));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'message' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#default_value' => $this->configuration['message'],
      '#required' => TRUE,
      '#rows' => '8',
      '#description' => t('The message to be displayed to the current user. You may include placeholders like [node:title], [user:account-name], [user:display-name] and [comment:body] to represent data that will be different each time message is sent. Not all placeholders will be available in all contexts.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['message'] = $form_state->getValue('message');
    unset($this->configuration['node']);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

}
