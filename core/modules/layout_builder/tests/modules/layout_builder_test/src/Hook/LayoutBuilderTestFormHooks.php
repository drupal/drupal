<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Form hook implementations for layout_builder_test.
 */
class LayoutBuilderTestFormHooks {

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for layout_builder_configure_block.
   */
  #[Hook('form_layout_builder_configure_block_alter')]
  public function formLayoutBuilderConfigureBlockAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    /** @var \Drupal\layout_builder\Form\ConfigureBlockFormBase $form_object */
    $form_object = $form_state->getFormObject();
    $form['layout_builder_test']['storage'] = [
      '#type' => 'item',
      '#title' => 'Layout Builder Storage: ' . $form_object->getSectionStorage()->getStorageId(),
    ];
    $form['layout_builder_test']['section'] = [
      '#type' => 'item',
      '#title' => 'Layout Builder Section: ' . $form_object->getCurrentSection()->getLayoutId(),
    ];
    $form['layout_builder_test']['component'] = [
      '#type' => 'item',
      '#title' => 'Layout Builder Component: ' . $form_object->getCurrentComponent()->getPluginId(),
    ];
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for layout_builder_configure_section.
   */
  #[Hook('form_layout_builder_configure_section_alter')]
  public function formLayoutBuilderConfigureSectionAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    /** @var \Drupal\layout_builder\Form\ConfigureSectionForm $form_object */
    $form_object = $form_state->getFormObject();
    $form['layout_builder_test']['storage'] = [
      '#type' => 'item',
      '#title' => 'Layout Builder Storage: ' . $form_object->getSectionStorage()->getStorageId(),
    ];
    $form['layout_builder_test']['section'] = [
      '#type' => 'item',
      '#title' => 'Layout Builder Section: ' . $form_object->getCurrentSection()->getLayoutId(),
    ];
    $form['layout_builder_test']['layout'] = [
      '#type' => 'item',
      '#title' => 'Layout Builder Layout: ' . $form_object->getCurrentLayout()->getPluginId(),
    ];
  }

}
