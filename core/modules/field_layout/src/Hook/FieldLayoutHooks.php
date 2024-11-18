<?php

namespace Drupal\field_layout\Hook;

use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_layout\FieldLayoutBuilder;
use Drupal\field_layout\Display\EntityDisplayWithLayoutInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field_layout\Form\FieldLayoutEntityFormDisplayEditForm;
use Drupal\field_layout\Form\FieldLayoutEntityViewDisplayEditForm;
use Drupal\field_layout\Entity\FieldLayoutEntityFormDisplay;
use Drupal\field_layout\Entity\FieldLayoutEntityViewDisplay;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_layout.
 */
class FieldLayoutHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.field_layout':
        $output = '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Field Layout module allows you to arrange fields into regions on forms and displays of entities such as nodes and users.') . '</p>';
        $output .= '<p>' . t('For more information, see the <a href=":field-layout-documentation">online documentation for the Field Layout module</a>.', [
          ':field-layout-documentation' => 'https://www.drupal.org/documentation/modules/field_layout',
        ]) . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['entity_view_display']->setClass(FieldLayoutEntityViewDisplay::class);
    $entity_types['entity_form_display']->setClass(FieldLayoutEntityFormDisplay::class);
    // The form classes are only needed when Field UI is installed.
    if (\Drupal::moduleHandler()->moduleExists('field_ui')) {
      $entity_types['entity_view_display']->setFormClass('edit', FieldLayoutEntityViewDisplayEditForm::class);
      $entity_types['entity_form_display']->setFormClass('edit', FieldLayoutEntityFormDisplayEditForm::class);
    }
  }

  /**
   * Implements hook_entity_view_alter().
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    if ($display instanceof EntityDisplayWithLayoutInterface) {
      \Drupal::classResolver(FieldLayoutBuilder::class)->buildView($build, $display);
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ContentEntityFormInterface && ($display = $form_object->getFormDisplay($form_state))) {
      if ($display instanceof EntityDisplayWithLayoutInterface) {
        \Drupal::classResolver(FieldLayoutBuilder::class)->buildForm($form, $display);
      }
    }
  }

}
