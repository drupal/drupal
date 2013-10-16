<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Block\ViewsBlock.
 */

namespace Drupal\views\Plugin\Block;

use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\Query\Query;
use Drupal\Component\Utility\Xss;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic Views block.
 *
 * @Block(
 *   id = "views_block",
 *   admin_label = @Translation("Views Block"),
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
  public function defaultConfiguration() {
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
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    $this->view->setDisplay($this->displayID);
    return 'views_block__' . $this->view->storage->id() . '_' . $this->view->current_display;
  }

}
