<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Hook;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderBefore;

/**
 * Hook implementations for layout_builder_test.
 */
class LayoutBuilderTestHooks {

  /**
   * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
   */
  #[Hook('plugin_filter_block__layout_builder_alter')]
  public function pluginFilterBlockLayoutBuilderAlter(array &$definitions, array $extra): void {
    // Explicitly remove the "Help" blocks from the list.
    unset($definitions['help_block']);
    // Explicitly remove the "Sticky at top of lists field_block".
    $disallowed_fields = ['sticky'];
    // Remove "Changed" field if this is the first section.
    if ($extra['delta'] === 0) {
      $disallowed_fields[] = 'changed';
    }
    foreach ($definitions as $plugin_id => $definition) {
      // Field block IDs are in the form 'field_block:{entity}:{bundle}:{name}',
      // for example 'field_block:node:article:revision_timestamp'.
      preg_match('/field_block:.*:.*:(.*)/', $plugin_id, $parts);
      if (isset($parts[1]) && in_array($parts[1], $disallowed_fields, TRUE)) {
        // Unset any field blocks that match our predefined list.
        unset($definitions[$plugin_id]);
      }
    }
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $extra['node']['bundle_with_section_field']['display']['layout_builder_test'] = [
      'label' => 'Extra label',
      'description' => 'Extra description',
      'weight' => 0,
    ];
    $extra['node']['bundle_with_section_field']['display']['layout_builder_test_2'] = [
      'label' => 'Extra Field 2',
      'description' => 'Extra Field 2 description',
      'weight' => 0,
      'visible' => FALSE,
    ];
    return $extra;
  }

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

  /**
   * Implements hook_entity_form_display_alter().
   */
  #[Hook('entity_form_display_alter', module: 'layout_builder')]
  public function layoutBuilderEntityFormDisplayAlter(EntityFormDisplayInterface $form_display, array $context): void {
    if ($context['form_mode'] === 'layout_builder') {
      $form_display->setComponent('status', ['type' => 'boolean_checkbox', 'settings' => ['display_label' => TRUE]]);
    }
  }

  /**
   * Implements hook_system_breadcrumb_alter().
   */
  #[Hook(
    'system_breadcrumb_alter',
    order: new OrderBefore(
      modules: ['layout_builder']
    )
  )]
  public function systemBreadcrumbAlter(Breadcrumb &$breadcrumb, RouteMatchInterface $route_match, array $context): void {
    $breadcrumb->addLink(Link::fromTextAndUrl('External link', Url::fromUri('http://www.example.com')));
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return ['block__preview_aware_block' => ['base hook' => 'block']];
  }

}
