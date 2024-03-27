<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Component;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Theme\ComponentNegotiator;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Defines a base class for component kernel tests.
 */
abstract class ComponentKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
  ];

  /**
   * Themes to install.
   *
   * @var string[]
   */
  protected static $themes = [];

  /**
   * The component negotiator.
   *
   * @return \Drupal\Core\Theme\ComponentNegotiator
   */
  protected ComponentNegotiator $negotiator;

  /**
   * The component plugin manager.
   *
   * @var \Drupal\Core\Theme\ComponentPluginManager
   */
  protected ComponentPluginManager $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    if (empty(static::$themes)) {
      throw new \Exception('You need to set the protected static $themes property on your test class, with the first item being the default theme.');
    }
    $this->container->get('theme_installer')->install(static::$themes);
    $this->installConfig('system');

    $system_theme_config = $this->container->get('config.factory')->getEditable('system.theme');
    $theme_name = reset(static::$themes);
    $system_theme_config
      ->set('default', $theme_name)
      ->save();
    $theme_manager = \Drupal::service('theme.manager');
    $active_theme = \Drupal::service('theme.initialization')->initTheme($theme_name);
    $theme_manager->setActiveTheme($active_theme);

    $this->negotiator = new ComponentNegotiator(
      $theme_manager,
      \Drupal::service('extension.list.module'),
    );
    $this->manager = \Drupal::service('plugin.manager.sdc');
  }

  /**
   * Renders a component for testing sake.
   *
   * @param array $component
   *   Component render array.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $metadata
   *   Bubble metadata.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   Crawler for introspecting the rendered component.
   */
  protected function renderComponentRenderArray(array $component, ?BubbleableMetadata $metadata = NULL): Crawler {
    $component = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'sdc-wrapper',
      ],
      'component' => $component,
    ];
    $metadata = $metadata ?: new BubbleableMetadata();
    $context = new RenderContext();
    $renderer = \Drupal::service('renderer');
    $output = $renderer->executeInRenderContext($context, fn () => $renderer->render($component));
    if (!$context->isEmpty()) {
      $metadata->addCacheableDependency($context->pop());
    }
    return new Crawler((string) $output);
  }

}
