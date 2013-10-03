<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Type\Formatter\FormatterInterface.
 */

namespace Drupal\field\Plugin\Type\Formatter;

use Drupal\Core\Entity\Field\FieldItemListInterface;
use Drupal\field\Plugin\PluginSettingsInterface;

/**
 * Interface definition for field widget plugins.
 */
interface FormatterInterface extends PluginSettingsInterface {

  /**
   * Returns a form to configure settings for the formatter.
   *
   * Invoked from \Drupal\field_ui\Form\FieldInstanceEditForm to allow
   * administrators to configure the formatter. The field_ui module takes care
   * of handling submitted form values.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   The form elements for the formatter settings.
   */
  public function settingsForm(array $form, array &$form_state);

  /**
   * Returns a short summary for the current formatter settings.
   *
   * If an empty result is returned, the formatter is assumed to have no
   * configurable settings, and no UI will be provided to display a settings
   * form.
   *
   * @return array()
   *   A short summary of the formatter settings.
   */
  public function settingsSummary();

  /**
   * Allows formatters to load information for field values being displayed.
   *
   * This should be used when a formatter needs to load additional information
   * from the database in order to render a field, for example a reference
   * field that displays properties of the referenced entities such as name or
   * type.
   *
   * This method operates on multiple entities. The $entities and $items
   * parameters are arrays keyed by entity ID. For performance reasons,
   * information for all involved entities should be loaded in a single query
   * where possible.
   *
   * Changes or additions to field values are done by alterings the $items
   * parameter by reference.
   *
   * @param array $entities_items
   *   Array of field values (Drupal\Core\Entity\Field\FieldItemListInterface),
   *   keyed by entity ID.
   */
  public function prepareView(array $entities_items);

  /**
   * Builds a renderable array for one field on one entity instance.
   *
   * @param \Drupal\Core\Entity\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   *
   * @return array
   *   A renderable array for a themed field with its label and all its values.
   */
  public function view(FieldItemListInterface $items);

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Entity\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items);

}
