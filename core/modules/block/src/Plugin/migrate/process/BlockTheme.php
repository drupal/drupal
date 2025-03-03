<?php

namespace Drupal\block\Plugin\migrate\process;

use Drupal\Core\Config\Config;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Determines the theme to use for a block.
 */
#[MigrateProcess('block_theme')]
class BlockTheme extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Contains the system.theme configuration object.
   */
  protected Config $themeConfig;

  /**
   * List of themes available on the destination.
   *
   * @var string[]
   */
  protected array $themes;

  /**
   * Constructs a BlockTheme object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\Config $theme_config
   *   The system.theme configuration factory object.
   * @param array $themes
   *   The list of themes available on the destination.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Config $theme_config, array $themes) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->themeConfig = $theme_config;
    $this->themes = $themes;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get('system.theme'),
      $container->get('theme_handler')->listInfo()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$theme, $default_theme, $admin_theme] = $value;

    // If the source theme exists on the destination, we're good.
    if (isset($this->themes[$theme])) {
      return $theme;
    }

    // If the source block is assigned to a region in the source default theme,
    // then assign it to the destination default theme.
    if (strtolower($theme) == strtolower($default_theme)) {
      return $this->themeConfig->get('default');
    }

    // If the source block is assigned to a region in the source admin theme,
    // then assign it to the destination admin theme.
    if ($admin_theme && strtolower($theme) == strtolower($admin_theme)) {
      return $this->themeConfig->get('admin');
    }

    // We couldn't map it to a D8 theme so just return the incoming theme.
    return $theme;
  }

}
