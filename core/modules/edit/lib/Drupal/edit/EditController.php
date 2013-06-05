<?php

/**
 * @file
 * Contains of \Drupal\edit\EditController.
 */

namespace Drupal\edit;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\edit\Ajax\FieldFormCommand;
use Drupal\edit\Ajax\FieldFormSavedCommand;
use Drupal\edit\Ajax\FieldFormValidationErrorsCommand;
use Drupal\edit\Ajax\MetadataCommand;

/**
 * Returns responses for Edit module routes.
 */
class EditController extends ContainerAware {

  /**
   * Returns the metadata for a set of fields.
   *
   * Given a list of field edit IDs as POST parameters, run access checks on the
   * entity and field level to determine whether the current user may edit them.
   * Also retrieves other metadata.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function metadata(Request $request) {
    $fields = $request->request->get('fields');
    if (!isset($fields)) {
      throw new NotFoundHttpException();
    }
    $metadataGenerator = $this->container->get('edit.metadata.generator');

    $metadata = array();
    foreach ($fields as $field) {
      list($entity_type, $entity_id, $field_name, $langcode, $view_mode) = explode('/', $field);

      // Load the entity.
      if (!$entity_type || !entity_get_info($entity_type)) {
        throw new NotFoundHttpException();
      }
      $entity = entity_load($entity_type, $entity_id);
      if (!$entity) {
        throw new NotFoundHttpException();
      }

      // Validate the field name and language.
      if (!$field_name || !($instance = field_info_instance($entity->entityType(), $field_name, $entity->bundle()))) {
        throw new NotFoundHttpException();
      }
      if (!$langcode || (field_valid_language($langcode) !== $langcode)) {
        throw new NotFoundHttpException();
      }

      $metadata[$field] = $metadataGenerator->generate($entity, $instance, $langcode, $view_mode);
    }

    return new JsonResponse($metadata);
  }

  /**
   * Returns AJAX commands to load in-place editors' attachments.
   *
   * Given a list of in-place editor IDs as POST parameters, render AJAX
   * commands to load those in-place editors.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function attachments(Request $request) {
    $response = new AjaxResponse();
    $editors = $request->request->get('editors');
    if (!isset($editors)) {
      throw new NotFoundHttpException();
    }

    $editorSelector = $this->container->get('edit.editor.selector');
    $elements['#attached'] = $editorSelector->getEditorAttachments($editors);
    drupal_process_attached($elements);

    return $response;
  }

  /**
   * Returns a single field edit form as an Ajax response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being edited.
   * @param string $field_name
   *   The name of the field that is being edited.
   * @param string $langcode
   *   The name of the language for which the field is being edited.
   * @param string $view_mode_id
   *   The view mode the field should be rerendered in.
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function fieldForm(EntityInterface $entity, $field_name, $langcode, $view_mode_id) {
    $response = new AjaxResponse();

    $form_state = array(
      'langcode' => $langcode,
      'no_redirect' => TRUE,
      'build_info' => array('args' => array($entity, $field_name)),
    );
    $form = drupal_build_form('edit_field_form', $form_state);

    if (!empty($form_state['executed'])) {
      // The form submission took care of saving the updated entity. Return the
      // updated view of the field.
      $entity = entity_load($form_state['entity']->entityType(), $form_state['entity']->id(), TRUE);
      // @todo Remove when http://drupal.org/node/1346214 is complete.
      $entity = $entity->getBCEntity();
      $output = field_view_field($entity, $field_name, $view_mode_id, $langcode);

      $response->addCommand(new FieldFormSavedCommand(drupal_render($output)));
    }
    else {
      $response->addCommand(new FieldFormCommand(drupal_render($form)));

      $errors = form_get_errors();
      if (count($errors)) {
        $response->addCommand(new FieldFormValidationErrorsCommand(theme('status_messages')));
      }
    }

    // When working with a hidden form, we don't want any CSS or JS to be loaded.
    if (isset($_POST['nocssjs']) && $_POST['nocssjs'] === 'true') {
      drupal_static_reset('drupal_add_css');
      drupal_static_reset('drupal_add_js');
    }

    return $response;
  }

}
