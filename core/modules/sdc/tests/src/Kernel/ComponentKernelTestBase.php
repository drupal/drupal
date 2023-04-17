<?php

declare(strict_types=1);

namespace Drupal\Tests\sdc\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sdc\ComponentNegotiator;
use Drupal\sdc\ComponentPluginManager;
use Drupal\Tests\sdc\Traits\ComponentRendererTrait;

/**
 * Defines a base class for component kernel tests.
 *
 * @internal
 */
abstract class ComponentKernelTestBase extends KernelTestBase {

  use ComponentRendererTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sdc',
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
   * @return \Drupal\sdc\ComponentNegotiator
   */
  protected ComponentNegotiator $negotiator;

  /**
   * The component plugin manager.
   *
   * @var \Drupal\sdc\ComponentPluginManager
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
    $system_theme_config
      ->set('default', reset(static::$themes))
      ->save();
    $this->negotiator = new ComponentNegotiator(
      \Drupal::service('theme.manager'),
      \Drupal::service('extension.list.module'),
    );
    $this->manager = \Drupal::service('plugin.manager.sdc');
  }

}
