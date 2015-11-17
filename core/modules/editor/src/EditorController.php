<?php

/**
 * @file
 * Contains \Drupal\editor\EditorController.
 */

namespace Drupal\editor;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\editor\Ajax\GetUntransformedTextCommand;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Editor module routes.
 */
class EditorController extends ControllerBase {

  /**
   * Returns an Ajax response to render a text field without transformation filters.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of which a formatted text field is being rerendered.
   * @param string $field_name
   *   The name of the (formatted text) field that that is being rerendered
   * @param string $langcode
   *   The name of the language for which the formatted text field is being
   *   rerendered.
   * @param string $view_mode_id
   *   The view mode the formatted text field should be rerendered in.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function getUntransformedText(EntityInterface $entity, $field_name, $langcode, $view_mode_id) {
    $response = new AjaxResponse();

    // Direct text editing is only supported for single-valued fields.
    $field = $entity->getTranslation($langcode)->$field_name;
    $editable_text = check_markup($field->value, $field->format, $langcode, array(FilterInterface::TYPE_TRANSFORM_REVERSIBLE, FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE));
    $response->addCommand(new GetUntransformedTextCommand($editable_text));

    return $response;
  }

  /**
   * Apply the necessary XSS filtering for using a certain text format's editor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\filter\FilterFormatInterface $filter_format
   *   The text format whose text editor (if any) will be used.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the XSS-filtered value.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if no value to filter is specified.
   *
   * @see editor_filter_xss()
   */
  public function filterXss(Request $request, FilterFormatInterface $filter_format) {
    $value = $request->request->get('value');
    if (!isset($value)) {
      throw new NotFoundHttpException();
    }

    // The original_format parameter will only exist when switching text format.
    $original_format_id = $request->request->get('original_format_id');
    $original_format = NULL;
    if (isset($original_format_id)) {
      $original_format = $this->entityManager()
        ->getStorage('filter_format')
        ->load($original_format_id);
    }

    return new JsonResponse(editor_filter_xss($value, $filter_format, $original_format));
  }

}
