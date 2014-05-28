<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Block\ViewsBlock.
 */

namespace Drupal\views\Plugin\Block;

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
      // Override the label to the dynamic title configured in the view.
      if (empty($this->configuration['views_label']) && $this->view->getTitle()) {
        $output['#title'] = Xss::filterAdmin($this->view->getTitle());
      }

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
  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    // Set the label to the static title configured in the view.
    if (!empty($configuration['views_label'])) {
      $configuration['label'] = $configuration['views_label'];
    }

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $settings = parent::defaultConfiguration();

    if ($this->displaySet) {
      $settings += $this->view->display_handler->blockSettings($settings);
    }

    // Set custom cache settings.
    if (isset($this->pluginDefinition['cache'])) {
      $settings['cache'] = $this->pluginDefinition['cache'];
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
    parent::blockSubmit($form, $form_state);
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
