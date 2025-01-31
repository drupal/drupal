<?php

declare(strict_types=1);

namespace Drupal\config_translation_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_translation_test.
 */
class ConfigTranslationTestHooks {

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, $type): void {
    // @see \Drupal\config_translation\Tests\ConfigTranslationUiThemeTest
    if ($file->getType() == 'theme' && $file->getName() == 'config_translation_test_theme') {
      $info['hidden'] = FALSE;
    }
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    // Remove entity definition for these entity types from config_test module.
    unset($entity_types['config_test_no_status']);
    unset($entity_types['config_query_test']);
  }

  /**
   * Implements hook_config_translation_info_alter().
   */
  #[Hook('config_translation_info_alter')]
  public function configTranslationInfoAlter(&$info): void {
    if (\Drupal::state()->get('config_translation_test_config_translation_info_alter')) {
      // Limit account settings config files to only one of them.
      $info['entity.user.admin_form']['names'] = ['user.settings'];
      // Add one more config file to the site information page.
      $info['system.site_information_settings']['names'][] = 'system.rss';
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for ConfigTranslationFormBase.
   *
   * Adds a list of configuration names to the top of the configuration
   * translation form.
   *
   * @see \Drupal\config_translation\Form\ConfigTranslationFormBase
   */
  #[Hook('form_config_translation_form_alter')]
  public function formConfigTranslationFormAlter(&$form, FormStateInterface $form_state) : void {
    if (\Drupal::state()->get('config_translation_test_alter_form_alter')) {
      $form['#base_altered'] = TRUE;
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for ConfigTranslationAddForm.
   *
   * Changes the title to include the source language.
   *
   * @see \Drupal\config_translation\Form\ConfigTranslationAddForm
   */
  #[Hook('form_config_translation_add_form_alter')]
  public function formConfigTranslationAddFormAlter(&$form, FormStateInterface $form_state) : void {
    if (\Drupal::state()->get('config_translation_test_alter_form_alter')) {
      $form['#altered'] = TRUE;
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for ConfigTranslationEditForm.
   *
   * Adds a column to the configuration translation edit form that shows the
   * current translation. Note that this column would not be displayed by
   * default, as the columns are hardcoded in
   * config_translation_manage_form_element.html.twig. The template would need
   * to be overridden for the column to be displayed.
   *
   * @see \Drupal\config_translation\Form\ConfigTranslationEditForm
   */
  #[Hook('form_config_translation_edit_form_alter')]
  public function formConfigTranslationEditFormAlter(&$form, FormStateInterface $form_state) : void {
    if (\Drupal::state()->get('config_translation_test_alter_form_alter')) {
      $form['#altered'] = TRUE;
    }
  }

}
