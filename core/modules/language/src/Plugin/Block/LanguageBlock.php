<?php

namespace Drupal\language\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Language switcher' block.
 *
 * @Block(
 *   id = "language_block",
 *   admin_label = @Translation("Language switcher"),
 *   category = @Translation("System"),
 *   deriver = "Drupal\language\Plugin\Derivative\LanguageBlock"
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
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

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
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('path.matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = $this->languageManager->isMultilingual() ? AccessResult::allowed() : AccessResult::forbidden();
    return $access->addCacheTags(['config:configurable_language_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
    $type = $this->getDerivativeId();
    $links = $this->languageManager->getLanguageSwitchLinks($type, Url::fromRoute($route_name));

    if (isset($links->links)) {
      $build = [
        '#theme' => 'links__language_block',
        '#links' => $links->links,
        '#attributes' => [
          'class' => [
            "language-switcher-{$links->method_id}",
          ],
        ],
        '#set_active_class' => TRUE,
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable in https://www.drupal.org/node/2232375.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
