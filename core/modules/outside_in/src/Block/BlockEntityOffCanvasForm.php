<?php

namespace Drupal\outside_in\Block;

use Drupal\block\BlockForm;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Provides form for block instance forms when used in the off-canvas tray.
 *
 * This form removes advanced sections of regular block form such as the
 * visibility settings, machine ID and region.
 */
class BlockEntityOffCanvasForm extends BlockForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Create link to full block form.
    $query = [];
    if ($destination = $this->getRequest()->query->get('destination')) {
      $query['destination'] = $destination;
    }
    $form['advanced_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Advanced options'),
      '#url' => $this->entity->toUrl('edit-form', ['query' => $query]),
      '#weight' => 1000,
    ];

    // Remove the ID and region elements.
    unset($form['id'], $form['region']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildVisibilityInterface(array $form, FormStateInterface $form_state) {
    // Do not display the visibility.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateVisibility(array $form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  protected function submitVisibility(array $form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginForm(BlockPluginInterface $block) {
    if ($block instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($block, 'offcanvas', 'configure');
    }
    return $block;
  }

}
