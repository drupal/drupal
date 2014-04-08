<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Block\LanguageBlock.
 */

namespace Drupal\language\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Language switcher' block.
 *
 * @Block(
 *   id = "language_block",
 *   admin_label = @Translation("Language switcher"),
 *   category = @Translation("System"),
 *   derivative = "Drupal\language\Plugin\Derivative\LanguageBlock"
 * )
 */
class LanguageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs an LanguageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  function access(AccountInterface $account) {
    return $this->languageManager->isMultilingual();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();
    $path = drupal_is_front_page() ? '<front>' : current_path();
    $type = $this->getDerivativeId();
    $links = $this->languageManager->getLanguageSwitchLinks($type, $path);

    if (isset($links->links)) {
      $build = array(
        '#theme' => 'links__language_block',
        '#links' => $links->links,
        '#attributes' => array(
          'class' => array(
            "language-switcher-{$links->method_id}",
          ),
        ),
        '#set_active_class' => TRUE,
      );
    }
    return $build;
  }

}
