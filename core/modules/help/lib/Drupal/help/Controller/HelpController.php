<?php
/**
 * @file
 * Contains \Drupal\help\Controller\HelpController.
 */

namespace Drupal\help\Controller;

use Drupal\Core\ControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for help routes.
 */
class HelpController implements ControllerInterface {

  /**
   * Stores the module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\help\Controller\HelpController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('module_handler'));
  }

  /**
   * Prints a page listing a glossary of Drupal terminology.
   *
   * @return string
   *   An HTML string representing the contents of help page.
   */
  public function helpMain() {
    // Add CSS.
    drupal_add_css(drupal_get_path('module', 'help') . '/help.css');
    $output = '<h2>' . t('Help topics') . '</h2><p>' . t('Help is available on the following items:') . '</p>' . $this->helpLinksAsList();
    return $output;
  }

  /**
   * Provides a formatted list of available help topics.
   *
   * @return string
   *   A string containing the formatted list.
   */
  protected function helpLinksAsList() {
    $empty_arg = drupal_help_arg();
    $module_info = system_rebuild_module_data();

    $modules = array();
    foreach ($this->moduleHandler->getImplementations('help') as $module) {
      if ($this->moduleHandler->invoke($module, 'help', array("admin/help#$module", $empty_arg))) {
        $modules[$module] = $module_info[$module]->info['name'];
      }
    }
    asort($modules);

    // Output pretty four-column list.
    $count = count($modules);
    $break = ceil($count / 4);
    $output = '<div class="clearfix"><div class="help-items"><ul>';
    $i = 0;
    foreach ($modules as $module => $name) {
      $output .= '<li>' . l($name, 'admin/help/' . $module) . '</li>';
      if (($i + 1) % $break == 0 && ($i + 1) != $count) {
        $output .= '</ul></div><div class="help-items' . ($i + 1 == $break * 3 ? ' help-items-last' : '') . '"><ul>';
      }
      $i++;
    }
    $output .= '</ul></div></div>';

    return $output;
  }

}
