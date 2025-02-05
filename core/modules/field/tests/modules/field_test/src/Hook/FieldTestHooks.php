<?php

declare(strict_types=1);

namespace Drupal\field_test\Hook;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\field_test\FieldTestHelper;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Hook implementations for field_test.
 */
class FieldTestHooks {

  /**
   * Implements hook_entity_display_build_alter().
   */
  #[Hook('entity_display_build_alter')]
  public function entityDisplayBuildAlter(&$output, $context): void {
    $display_options = $context['display']->getComponent('test_field');
    if (isset($display_options['settings']['alter'])) {
      $output['test_field'][] = ['#markup' => 'field_test_entity_display_build_alter'];
    }
    if (isset($output['test_field'])) {
      $output['test_field'][] = ['#markup' => 'entity language is ' . $context['entity']->language()->getId()];
    }
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(&$element, FormStateInterface $form_state, $context): void {
    // Set a message if this is for the form displayed to set default value for
    // the field.
    if ($context['default']) {
      \Drupal::messenger()->addStatus('From hook_field_widget_single_element_form_alter(): Default form is true.');
    }
  }

  /**
   * Implements hook_field_widget_complete_form_alter().
   */
  #[Hook('field_widget_complete_form_alter')]
  public function fieldWidgetCompleteFormAlter(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    $this->alterWidget("hook_field_widget_complete_form_alter", $field_widget_complete_form, $form_state, $context);
  }

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_test_field_widget_multiple_form_alter')]
  public function fieldWidgetCompleteTestFieldWidgetMultipleFormAlter(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    $this->alterWidget("hook_field_widget_complete_WIDGET_TYPE_form_alter", $field_widget_complete_form, $form_state, $context);
  }

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_test_field_widget_multiple_single_value_form_alter')]
  public function fieldWidgetCompleteTestFieldWidgetMultipleSingleValueFormAlter(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    $this->alterWidget("hook_field_widget_complete_WIDGET_TYPE_form_alter", $field_widget_complete_form, $form_state, $context);
  }

  /**
   * Implements hook_query_TAG_alter() for tag 'efq_table_prefixing_test'.
   *
   * @see \Drupal\system\Tests\Entity\EntityFieldQueryTest::testTablePrefixing()
   */
  #[Hook('query_efq_table_prefixing_test_alter')]
  public function queryEfqTablePrefixingTestAlter(&$query): void {
    // Add an additional join onto the entity base table. This will cause an
    // exception if the EFQ does not properly prefix the base table.
    $query->join('entity_test', 'et2', '[%alias].[id] = [entity_test].[id]');
  }

  /**
   * Implements hook_query_TAG_alter() for tag 'efq_metadata_test'.
   *
   * @see \Drupal\system\Tests\Entity\EntityQueryTest::testMetaData()
   */
  #[Hook('query_efq_metadata_test_alter')]
  public function queryEfqMetadataTestAlter(&$query): void {
    FieldTestHelper::memorize('field_test_query_efq_metadata_test_alter', $query->getMetadata('foo'));
  }

  /**
   * Implements hook_entity_extra_field_info_alter().
   */
  #[Hook('entity_extra_field_info_alter')]
  public function entityExtraFieldInfoAlter(&$info): void {
    // Remove all extra fields from the 'no_fields' content type;
    unset($info['node']['no_fields']);
  }

  /**
   * Implements hook_entity_bundle_field_info_alter().
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityBundleFieldInfoAlter(&$fields, EntityTypeInterface $entity_type, $bundle): void {
    if (($field_name = \Drupal::state()->get('field_test_constraint', FALSE)) && $entity_type->id() == 'entity_test' && $bundle == 'entity_test' && !empty($fields[$field_name])) {
      // Set a property constraint using
      // \Drupal\Core\Field\FieldConfigInterface::setPropertyConstraints().
      $fields[$field_name]->setPropertyConstraints('value', [
        'TestField' => [
          'value' => -2,
          'message' => "$field_name does not accept the value -2.",
        ],
      ]);
      // Add a property constraint using
      // \Drupal\Core\Field\FieldConfigInterface::addPropertyConstraints().
      $fields[$field_name]->addPropertyConstraints('value', ['Range' => ['min' => 0, 'max' => 32]]);
    }
  }

  /**
   * Implements hook_field_ui_preconfigured_options_alter().
   */
  #[Hook('field_ui_preconfigured_options_alter')]
  public function fieldUiPreconfiguredOptionsAlter(array &$options, $field_type): void {
    if ($field_type === 'test_field_with_preconfigured_options') {
      $options['custom_options']['entity_view_display']['settings'] = ['test_formatter_setting_multiple' => 'altered dummy test string'];
    }
  }

  /**
   * Implements hook_field_info_entity_type_ui_definitions_alter().
   */
  #[Hook('field_info_entity_type_ui_definitions_alter')]
  public function fieldInfoEntityTypeUiDefinitionsAlter(array &$ui_definitions, string $entity_type_id): void {
    if ($entity_type_id === 'node') {
      $ui_definitions['boolean']['label'] = new TranslatableMarkup('Boolean (overridden by alter)');
    }
  }

  /**
   * Implements hook_entity_query_alter().
   *
   * @see Drupal\KernelTests\Core\Entity\EntityQueryTest::testAlterHook
   */
  #[Hook('entity_query_alter')]
  public function entityQueryAlter(QueryInterface $query) : void {
    if ($query->hasTag('entity_query_alter_hook_test')) {
      $query->condition('id', '5', '<>');
    }
  }

  /**
   * Implements hook_entity_query_ENTITY_TYPE_alter() for 'entity_test_mulrev'.
   *
   * @see Drupal\KernelTests\Core\Entity\EntityQueryTest::testAlterHook
   */
  #[Hook('entity_query_entity_test_mulrev_alter')]
  public function entityQueryEntityTestMulrevAlter(QueryInterface $query) : void {
    if ($query->hasTag('entity_query_entity_test_mulrev_alter_hook_test')) {
      $query->condition('id', '7', '<>');
    }
  }

  /**
   * Implements hook_entity_query_tag__TAG_alter() for 'entity_query_alter_tag_test'.
   *
   * @see Drupal\KernelTests\Core\Entity\EntityQueryTest::testAlterHook
   */
  #[Hook('entity_query_tag__entity_query_alter_tag_test_alter')]
  public function entityQueryTagEntityQueryAlterTagTestAlter(QueryInterface $query) : void {
    $query->condition('id', '13', '<>');
  }

  /**
   * Implements hook_entity_query_tag__ENTITY_TYPE__TAG_alter().
   *
   * Entity type is 'entity_test_mulrev' and tag is
   * 'entity_query_entity_test_mulrev_alter_tag_test'.
   *
   * @see Drupal\KernelTests\Core\Entity\EntityQueryTest::testAlterHook
   */
  #[Hook('entity_query_tag__entity_test_mulrev__entity_query_entity_test_mulrev_alter_tag_test_alter')]
  public function entityQueryTagEntityTestMulrevEntityQueryEntityTestMulrevAlterTagTestAlter(QueryInterface $query) : void {
    $query->condition('id', '15', '<>');
  }

  /**
   * Implements hook_field_storage_config_create().
   */
  #[Hook('field_storage_config_create')]
  public function fieldStorageConfigCreate(FieldStorageConfigInterface $field_storage): void {
    $args = func_get_args();
    FieldTestHelper::memorize(__METHOD__, $args);
  }

  /**
   * Implements hook_entity_reference_selection_alter().
   */
  #[Hook('entity_reference_selection_alter')]
  public function entityReferenceSelectionAlter(array &$definitions): void {
    if (\Drupal::state()->get('field_test_disable_broken_entity_reference_handler')) {
      unset($definitions['broken']);
    }
  }

  /**
   * Sets up alterations for widget alter tests.
   *
   * @see \Drupal\field\Tests\FormTest::widgetAlterTest()
   */
  public function alterWidget($hook, array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    $elements = &$field_widget_complete_form['widget'];
    // Set a message if this is for the form displayed to set default value for
    // the field.
    if ($context['default']) {
      \Drupal::messenger()->addStatus("From $hook(): Default form is true.");
    }
    $alter_info = \Drupal::state()->get("field_test.widget_alter_test");
    $name = $context['items']->getFieldDefinition()->getName();
    if (!empty($alter_info) && $hook === $alter_info['hook'] && $name === $alter_info['field_name']) {
      $elements['#prefix'] = "From $hook(): prefix on $name parent element.";
      foreach (Element::children($elements) as $delta => $element) {
        $elements[$delta]['#suffix'] = "From $hook(): suffix on $name child element.";
      }
    }
  }

}
