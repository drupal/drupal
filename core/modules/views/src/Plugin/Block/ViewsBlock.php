<?php

namespace Drupal\views\Plugin\Block;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Element\View;
use Drupal\views\Plugin\Derivative\ViewsBlock as ViewsBlockDeriver;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a generic Views block.
 */
#[Block(
  id: "views_block",
  admin_label: new TranslatableMarkup("Views Block"),
  deriver: ViewsBlockDeriver::class
)]
class ViewsBlock extends ViewsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // If the block plugin is invalid, there is nothing to do.
    if (!method_exists($this->view->display_handler, 'preBlockBuild')) {
      return [];
    }
    $this->view->display_handler->preBlockBuild($this);

    $args = [];
    foreach ($this->view->display_handler->getHandlers('argument') as $argument_name => $argument) {
      // Initialize the argument value. Work around a limitation in
      // \Drupal\views\ViewExecutable::_buildArguments() that skips processing
      // later arguments if an argument with default action "ignore" and no
      // argument is provided.
      $args[$argument_name] = $argument->options['default_action'] == 'ignore' ? 'all' : NULL;

      if (!empty($this->context[$argument_name])) {
        if ($value = $this->context[$argument_name]->getContextValue()) {

          // Context values are often entities, but views arguments expect to
          // receive just the entity ID, convert it.
          if ($value instanceof EntityInterface) {
            $value = $value->id();
          }
          $args[$argument_name] = $value;
        }
      }
    }

    // We ask ViewExecutable::buildRenderable() to avoid creating a render cache
    // entry for the view output by passing FALSE, because we're going to cache
    // the whole block instead.
    if ($output = $this->view->buildRenderable($this->displayID, array_values($args), FALSE)) {
      // Before returning the block output, convert it to a renderable array
      // with contextual links.
      $this->addContextualLinks($output);

      // Block module expects to get a final render array, without another
      // top-level #pre_render callback. So, here we make sure that Views'
      // #pre_render callback has already been applied.
      $output = View::preRenderViewElement($output);

      // Inject the overridden block title into the view.
      if (!empty($this->configuration['views_label'])) {
        $this->view->setTitle($this->configuration['views_label']);
      }

      // Override the block title to match the view title.
      if ($this->view->getTitle()) {
        $output['#title'] = ['#markup' => $this->view->getTitle(), '#allowed_tags' => Xss::getHtmlTagList()];
      }

      // When view_build is empty, the actual render array output for this View
      // is going to be empty. In that case, return just #cache, so that the
      // render system knows the reasons (cache contexts & tags) why this Views
      // block is empty, and can cache it accordingly.
      if (empty($output['view_build'])) {
        $output = ['#cache' => $output['#cache']];
      }

      return $output;
    }

    return [];
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
  public function blockForm($form, FormStateInterface $form_state) {
    if ($this->displaySet) {
      return $this->view->display_handler->blockForm($this, $form, $form_state);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($this->displaySet) {
      $this->view->display_handler->blockValidate($this, $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
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
