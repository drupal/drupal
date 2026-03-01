<?php

namespace Drupal\editor;

use Drupal\Core\Controller\ControllerBase;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Editor module routes.
 */
class EditorController extends ControllerBase {

  public function __construct(protected ?Element $editorElement = NULL) {
    if (!$editorElement) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $editorElement argument is deprecated in drupal:11.4.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3568146', E_USER_DEPRECATED);
      $this->editorElement = \Drupal::service('editor.element');
    }
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
   * @see \Drupal\editor\Element::filterXss()
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
      $original_format = $this->entityTypeManager()
        ->getStorage('filter_format')
        ->load($original_format_id);
    }

    return new JsonResponse($this->editorElement->filterXss($value, $filter_format, $original_format));
  }

}
