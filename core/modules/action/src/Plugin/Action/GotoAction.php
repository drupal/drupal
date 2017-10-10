<?php

namespace Drupal\action\Plugin\Action;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects to a different URL.
 *
 * @Action(
 *   id = "action_goto_action",
 *   label = @Translation("Redirect to URL"),
 *   type = "system"
 * )
 */
class GotoAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The unrouted URL assembler service.
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   */
  protected $unroutedUrlAssembler;

  /**
   * Constructs a new DeleteNode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The tempstore factory.
   * @param \Drupal\Core\Utility\UnroutedUrlAssemblerInterface $url_assembler
   *   The unrouted URL assembler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $dispatcher, UnroutedUrlAssemblerInterface $url_assembler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dispatcher = $dispatcher;
    $this->unroutedUrlAssembler = $url_assembler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('event_dispatcher'), $container->get('unrouted_url_assembler'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $url = $this->configuration['url'];
    // Leave external URLs unchanged, and assemble others as absolute URLs
    // relative to the site's base URL.
    if (!UrlHelper::isExternal($url)) {
      $parts = UrlHelper::parse($url);
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      if ($parts['path'] === '<front>') {
        $parts['path'] = '';
      }
      $uri = 'base:' . $parts['path'];
      $options = [
        'query' => $parts['query'],
        'fragment' => $parts['fragment'],
        'absolute' => TRUE,
      ];
      // Treat this as if it's user input of a path relative to the site's
      // base URL.
      $url = $this->unroutedUrlAssembler->assemble($uri, $options);
    }
    $response = new RedirectResponse($url);
    $listener = function ($event) use ($response) {
      $event->setResponse($response);
    };
    // Add the listener to the event dispatcher.
    $this->dispatcher->addListener(KernelEvents::RESPONSE, $listener);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'url' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('The URL to which the user should be redirected. This can be an internal URL like /node/1234 or an external URL like @url.', ['@url' => 'http://example.com']),
      '#default_value' => $this->configuration['url'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['url'] = $form_state->getValue('url');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowed();
    return $return_as_object ? $access : $access->isAllowed();
  }

}
