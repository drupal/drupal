<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Condition\RequestPath.
 */

namespace Drupal\system\Plugin\Condition;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Request Path' condition.
 *
 * @Condition(
 *   id = "request_path",
 *   label = @Translation("Request Path"),
 * )
 */
class RequestPath extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * An alias manager to find the alias for the current system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs a RequestPath condition plugin.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   An alias manager to find the alias for the current system path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher, RequestStack $request_stack, CurrentPathStack $current_path, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('path.alias_manager'),
      $container->get('path.matcher'),
      $container->get('request_stack'),
      $container->get('path.current'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('pages' => '') + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['pages'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#default_value' => $this->configuration['pages'],
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %user for the current user's page and %user-wildcard for every user page. %front is the front page.", array(
        '%user' => '/user',
        '%user-wildcard' => '/user/*',
        '%front' => '<front>',
      )),
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['pages'] = $form_state->getValue('pages');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $pages = array_map('trim', explode("\n", $this->configuration['pages']));
    $pages = implode(', ', $pages);
    if (!empty($this->configuration['negate'])) {
      return $this->t('Do not return true on the following pages: @pages', array('@pages' => $pages));
    }
    return $this->t('Return true on the following pages: @pages', array('@pages' => $pages));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Convert path to lowercase. This allows comparison of the same path
    // with different case. Ex: /Page, /page, /PAGE.
    $pages = Unicode::strtolower($this->configuration['pages']);
    if (!$pages) {
      return TRUE;
    }

    $request = $this->requestStack->getCurrentRequest();
    // Compare the lowercase path alias (if any) and internal path.
    $path = rtrim($this->currentPath->getPath($request), '/');
    $path_alias = Unicode::strtolower($this->aliasManager->getAliasByPath($path));

    return $this->pathMatcher->matchPath($path_alias, $pages) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pages));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    // @todo Add a url.path cache context in
    //   https://www.drupal.org/node/2521978.
    $contexts[] = 'url';
    return $contexts;
  }

}
