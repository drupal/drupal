<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\block\block\ViewsBlock.
 */

namespace Drupal\views\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Provides a generic Views block.
 *
 * @Plugin(
 *   id = "views_block",
 *   subject = @Translation("Views Block"),
 *   module = "views",
 *   derivative = "Drupal\views\Plugin\Derivative\ViewsBlock"
 * )
 */
class ViewsBlock extends BlockBase {

  /**
   * The View executable object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The display ID being used for this View.
   *
   * @var string
   */
  protected $displayID;

  /**
   * Overrides \Drupal\Component\Plugin\PluginBase::__construct().
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    parent::__construct($configuration, $plugin_id, $discovery);

    list($plugin, $delta) = explode(':', $this->getPluginId());
    list($name, $this->displayID) = explode('-', $delta, 2);
    // Load the view.
    $this->view = views_get_view($name);
    $this->view->setDisplay($this->displayID);
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockAccess().
   */
  public function blockAccess() {
    return $this->view->access($this->displayID);
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    // Set the default subject to '' so the views internal title is used.
    $form['settings']['title']['#default_value'] = '';
    $form['settings']['title']['#access'] = FALSE;
    return $form;
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function blockBuild() {
    $output = $this->view->executeDisplay($this->displayID);
    // Set the subject to the title configured in the view.
    $this->configuration['subject'] = filter_xss_admin($this->view->getTitle());
    // Before returning the block output, convert it to a renderable array
    // with contextual links.
    views_add_block_contextual_links($output, $this->view, $this->displayID);
    $this->view->destroy();
    return $output;
  }

}
