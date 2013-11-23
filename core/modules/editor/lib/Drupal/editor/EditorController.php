<?php

/**
 * @file
 * Contains \Drupal\editor\EditorController.
 */

namespace Drupal\editor;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\editor\Ajax\GetUntransformedTextCommand;
use Drupal\editor\Form\EditorImageDialog;
use Drupal\editor\Form\EditorLinkDialog;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Returns responses for Editor module routes.
 */
class EditorController extends ContainerAware {

  /**
   * Returns an Ajax response to render a text field without transformation filters.
   *
   * @param int $entity
   *   The entity of which a processed text field is being rerendered.
   * @param string $field_name
   *   The name of the (processed text) field that that is being rerendered
   * @param string $langcode
   *   The name of the language for which the processed text field is being
   *   rererendered.
   * @param string $view_mode_id
   *   The view mode the processed text field should be rerendered in.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function getUntransformedText(EntityInterface $entity, $field_name, $langcode, $view_mode_id) {
    $response = new AjaxResponse();

    // Direct text editing is only supported for single-valued fields.
    $field = $entity->getTranslation($langcode)->$field_name;
    $editable_text = check_markup($field->value, $field->format, $langcode, FALSE, array(FILTER_TYPE_TRANSFORM_REVERSIBLE, FILTER_TYPE_TRANSFORM_IRREVERSIBLE));
    $response->addCommand(new GetUntransformedTextCommand($editable_text));

    return $response;
  }

}
