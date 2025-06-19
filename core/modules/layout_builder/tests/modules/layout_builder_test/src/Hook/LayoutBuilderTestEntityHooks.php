<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Hook;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Entity hook implementations for layout_builder_test.
 */
class LayoutBuilderTestEntityHooks {

  /**
   * Implements hook_ENTITY_TYPE_view().
   */
  #[Hook('node_view')]
  public function nodeView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode): void {
    if ($display->getComponent('layout_builder_test')) {
      $build['layout_builder_test'] = [
        '#markup' => 'Extra, Extra read all about it.',
      ];
    }
    if ($display->getComponent('layout_builder_test_2')) {
      $build['layout_builder_test_2'] = [
        '#markup' => 'Extra Field 2 is hidden by default.',
      ];
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
   * Implements hook_entity_form_display_alter().
   */
  #[Hook('entity_form_display_alter', module: 'layout_builder')]
  public function layoutBuilderEntityFormDisplayAlter(EntityFormDisplayInterface $form_display, array $context): void {
    if ($context['form_mode'] === 'layout_builder') {
      $form_display->setComponent('status', ['type' => 'boolean_checkbox', 'settings' => ['display_label' => TRUE]]);
    }
  }

}
