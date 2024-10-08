<?php

namespace Drupal\help\Plugin\HelpSection;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\help\Attribute\HelpSection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the module topics list section for the help page.
 */
#[HelpSection(
  id: 'hook_help',
  title: new TranslatableMarkup('Module overviews'),
  description: new TranslatableMarkup('Module overviews are provided by modules. Overviews available for your installed modules:')
)]
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
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler,
    protected ModuleExtensionList $moduleExtensionList,
  ) {
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
      $container->get('module_handler'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    $topics = [];
    $this->moduleHandler->invokeAllWith(
      'help',
      function (callable $hook, string $module) use (&$topics) {
        $title = $this->moduleExtensionList->getName($module);
        $topics[$title] = Link::createFromRoute($title, 'help.page', ['name' => $module]);
      }
    );

    // Sort topics by title, which is the array key above.
    ksort($topics);
    return $topics;
  }

}
