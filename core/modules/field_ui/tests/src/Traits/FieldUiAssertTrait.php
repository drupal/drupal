<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Traits;

/**
 * Provides assertions and helpers for Field UI test classes.
 */
trait FieldUiAssertTrait {

  /**
   * Helper function that returns the name of the group that a field is in.
   *
   * @param string $field_type
   *   The name of the field type.
   *
   * @return string|null
   *   Group name
   */
  protected function getFieldFromGroup($field_type): ?string {
    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager */
    $field_type_plugin_manager = \Drupal::service('plugin.manager.field.field_type');
    $grouped_field_types = $field_type_plugin_manager->getGroupedDefinitions($field_type_plugin_manager->getUiDefinitions());
    foreach ($grouped_field_types as $group => $field_types) {
      if (array_key_exists($field_type, $field_types)) {
        return $group;
      }
    }
    return NULL;
  }

  /**
   * Asserts that the field appears on the overview form.
   *
   * @param string $label
   *   The field label.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function assertFieldExistsOnOverview(string $label): void {
    $field_labels = array_map(
      fn ($element): string => html_entity_decode($element->getHtml()),
      $this->getSession()->getPage()->findAll('css', 'table#field-overview tr td:first-child .field-label-text'),
    );
    $this->assertContains($label, $field_labels);
  }

  /**
   * Asserts that the field does not appear on the overview form.
   *
   * @param string $label
   *   The field label.
   */
  protected function assertFieldDoesNotExistOnOverview(string $label): void {
    $xpath = $this->assertSession()
      ->buildXPathQuery("//table[@id=\"field-overview\"]//tr/td[1 and text() = :label]", [
        ':label' => $label,
      ]);
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    $this->assertSession()->assert($element === NULL, sprintf('A field "%s" appears on this page, but it should not.', $label));
  }

  /**
   * Asserts that a header cell appears on a table.
   *
   * @param string $table_id
   *   The HTML attribute value to target a given table.
   * @param string $label
   *   The cell label.
   */
  protected function assertTableHeaderExistsByLabel(string $table_id, string $label): void {
    $expression = '//table[@id=:id]//tr//th[1 and text() = :label]';
    $xpath = $this->assertSession()->buildXPathQuery($expression, [
      ':id' => $table_id,
      ':label' => $label,
    ]);
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    $this->assertSession()->assert($element !== NULL, sprintf('Table header not found by label: "%s".', $label));
  }

}
