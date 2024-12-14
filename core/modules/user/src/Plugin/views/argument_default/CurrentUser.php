<?php

namespace Drupal\user\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsArgumentDefault;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract the current user.
 *
 * This plugin actually has no options so it does not need to do a great deal.
 */
#[ViewsArgumentDefault(
  id: 'current_user',
  title: new TranslatableMarkup('User ID from logged in user'),
)]
class CurrentUser extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * CurrentUser constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface|null $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ?AccountInterface $currentUser = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if ($this->currentUser === NULL) {
      @trigger_error('Calling ' . __CLASS__ . '::__construct() without the $currentUser argument is deprecated in drupal:11.2.0 and is required in drupal:12.0.0. See https://www.drupal.org/node/3347878', E_USER_DEPRECATED);
      $this->currentUser = \Drupal::currentUser();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('current_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return $this->currentUser->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

}
