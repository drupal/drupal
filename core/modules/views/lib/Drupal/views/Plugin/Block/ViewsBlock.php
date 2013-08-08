<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Block\ViewsBlock.
 */

namespace Drupal\views\Plugin\Block;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Component\Utility\Xss;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class ViewsBlock extends ViewsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $this->view->display_handler->preBlockBuild($this);

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
   * {@inheritdoc}
   */
  public function settings() {
    $settings = array();

    if ($this->displaySet) {
      return $this->view->display_handler->blockSettings($settings);
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {
    if ($this->displaySet) {
      return $this->view->display_handler->blockForm($this, $form, $form_state);
    }

    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, &$form_state) {
    if ($this->displaySet) {
      $this->view->display_handler->blockValidate($this, $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    if ($this->displaySet) {
      $this->view->display_handler->blockSubmit($this, $form, $form_state);
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


  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    $this->view->setDisplay($this->displayID);
    return 'views_block__' . $this->view->storage->id() . '_' . $this->view->current_display;
  }

}
