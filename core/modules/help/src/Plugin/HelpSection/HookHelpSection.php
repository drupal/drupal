<?php

namespace Drupal\help\Plugin\HelpSection;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the module topics list section for the help page.
 *
 * @HelpSection(
 *   id = "hook_help",
 *   title = @Translation("Module overviews"),
 *   description = @Translation("Module overviews are provided by modules. Overviews available for your installed modules:"),
 * )
 */
class HookHelpSection extends HelpSectionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a HookHelpSection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    $topics = [];
    foreach ($this->moduleHandler->getImplementations('help') as $module) {
      $title = $this->moduleHandler->getName($module);
      $topics[$title] = Link::createFromRoute($title, 'help.page', ['name' => $module]);
    }

    // Sort topics by title, which is the array key above.
    ksort($topics);
    return $topics;
  }

}
