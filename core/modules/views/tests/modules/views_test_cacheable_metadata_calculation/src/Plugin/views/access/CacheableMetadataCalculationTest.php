<?php

namespace Drupal\views_test_cacheable_metadata_calculation\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Tests plugin that reports when cacheable metadata is being calculated.
 *
 * @ViewsAccess(
 *   id = "test_cacheable_metadata_access",
 *   title = @Translation("Cacheable metadata calculation test access plugin"),
 *   help = @Translation("Provides a test access plugin that reports when cacheable metadata is being calculated.")
 * )
 */
class CacheableMetadataCalculationTest extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a CacheableMetadataCalculationTest access plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $this->cacheableMetadataHasBeenAccessed();
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $this->cacheableMetadataHasBeenAccessed();
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $this->cacheableMetadataHasBeenAccessed();
    return Cache::PERMANENT;
  }

  /**
   * Sets a flag to inform tests that cacheable metadata has been accessed.
   */
  protected function cacheableMetadataHasBeenAccessed() {
    $this->state->set('views_test_cacheable_metadata_has_been_accessed', TRUE);
  }

}
