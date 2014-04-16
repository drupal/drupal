<?php

/**
 * @file
 * Hooks provided by the Edit module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to alter in-place editor plugin metadata.
 *
 * This hook is called after the in-place editor plugins have been discovered,
 * but before they are cached. Hence any alterations will be cached.
 *
 * @param array &$editors
 *   An array of metadata on existing in-place editors, as collected by the
 *   annotation discovery mechanism.
 *
 * @see \Drupal\quickedit\Annotation\InPlaceEditor
 * @see \Drupal\quickedit\Plugin\EditorManager
 */
function hook_quickedit_editor_alter(&$editors) {
  // Cleanly override editor.module's in-place editor plugin.
  $editors['editor']['class'] = 'Drupal\advanced_editor\Plugin\quickedit\editor\AdvancedEditor';
}

/**
 * Returns a renderable array for the value of a single field in an entity.
 *
 * To integrate with in-place field editing when a non-standard render pipeline
 * is used (FieldItemListInterface::view() is not sufficient to render back the
 * field following in-place editing in the exact way it was displayed
 * originally), implement this hook.
 *
 * Edit module integrates with HTML elements with data-edit-field-id attributes.
 * For example:
 *   data-edit-field-id="node/1/<field-name>/und/<module-name>-<custom-id>"
 * After the editing is complete, this hook is invoked on the module with
 * the custom render pipeline identifier (last part of data-edit-field-id) to
 * re-render the field. Use the same logic used when rendering the field for
 * the original display.
 *
 * The implementation should take care of invoking the prepare_view steps. It
 * should also respect field access permissions.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity containing the field to display.
 * @param string $field_name
 *   The name of the field to display.
 * @param string $view_mode_id
 *   View mode ID for the custom render pipeline this field view was destined
 *   for. This is not a regular view mode ID for the Entity/Field API render
 *   pipeline and is provided by the renderer module instead. An example could
 *   be Views' render pipeline. In the example of Views, the view mode ID would
 *   probably contain the View's ID, display and the row index. Views would
 *   know the internal structure of this ID. The only structure imposed on this
 *   ID is that it contains dash separated values and the first value is the
 *   module name. Only that module's hook implementation will be invoked. Eg.
 *   'views-...-...'.
 * @param string $langcode
 *   (Optional) The language code the field values are to be shown in.
 *
 * @return
 *   A renderable array for the field value.
 *
 * @see \Drupal\Core\Field\FieldItemListInterface::view()
 */
function hook_quickedit_render_field(Drupal\Core\Entity\EntityInterface $entity, $field_name, $view_mode_id, $langcode) {
  return array(
    '#prefix' => '<div class="example-markup">',
    'field' => $entity->getTranslation($langcode)->get($field_name)->view($view_mode_id),
    '#suffix' => '</div>',
  );
}

/**
 * @} End of "addtogroup hooks".
 */
