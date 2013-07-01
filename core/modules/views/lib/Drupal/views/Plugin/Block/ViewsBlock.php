<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Block\ViewsBlock.
 */

namespace Drupal\views\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Component\Utility\Xss;

/**
 * Provides a generic Views block.
 *
 * @Plugin(
 *   id = "views_block",
 *   admin_label = @Translation("Views Block"),
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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    list($plugin, $delta) = explode(':', $this->getPluginId());
    list($name, $this->displayID) = explode('-', $delta, 2);
    // Load the view.
    $this->view = views_get_view($name);
  }

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    return $this->view->access($this->displayID);
  }

  /**
   * Overrides \Drupal\block\BlockBase::form().
   */
  public function form($form, &$form_state) {
    $form = parent::form($form, $form_state);

    // Set the default label to '' so the views internal title is used.
    $form['label']['#default_value'] = '';
    $form['label']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($output = $this->view->executeDisplay($this->displayID)) {
      // Set the label to the title configured in the view.
      $this->configuration['label'] = Xss::filterAdmin($this->view->getTitle());
      // Before returning the block output, convert it to a renderable array
      // with contextual links.
      $this->addContextualLinks($output);
      return $output;
    }

    return array();
  }

  /**
   * Converts Views block content to a renderable array with contextual links.
   *
   * @param string|array $output
   *   An string|array representing the block. This will be modified to be a
   *   renderable array, containing the optional '#contextual_links' property (if
   *   there are any contextual links associated with the block).
   * @param string $block_type
   *   The type of the block. If it's 'block' it's a regular views display,
   *   but 'exposed_filter' exist as well.
   */
  protected function addContextualLinks(&$output, $block_type = 'block') {
    // Do not add contextual links to an empty block.
    if (!empty($output)) {
      // Contextual links only work on blocks whose content is a renderable
      // array, so if the block contains a string of already-rendered markup,
      // convert it to an array.
      if (is_string($output)) {
        $output = array('#markup' => $output);
      }
      // Add the contextual links.
      views_add_contextual_links($output, $block_type, $this->view, $this->displayID);
    }
  }

  /**
   * Generates a views block instance ID.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $manager
   *   The block storage controller.
   *
   * @return string
   *   The new block instance ID.
   */
   public function generateBlockInstanceID(EntityStorageControllerInterface $manager) {
     $this->view->setDisplay($this->displayID);
     $original_id = 'views_block__' . $this->view->storage->id() . '_' . $this->view->current_display;

     // Get an array of block IDs without the theme prefix.
     $block_ids = array_map(function ($block_id) {
       $parts = explode('.', $block_id);
       return end($parts);
     }, array_keys($manager->loadMultiple()));

     // Iterate through potential IDs until we get a new one. E.g.
     // 'views_block__MYVIEW_PAGE_1_2'
     $count = 1;
     $id = $original_id;
     while (in_array($id, $block_ids)) {
       $id = $original_id . '_' . ++$count;
     }

     return $id;
   }

}
