<?php
/**
 * @file
 * Contains \Drupal\content_translation\Form\ContentTranslationForm.
 */

namespace Drupal\content_translation\Form;

use Drupal\Core\Language\Language;
use Symfony\Component\HttpFoundation\Request;

/**
 * Temporary form controller for content_translation module.
 */
class ContentTranslationForm {

  /**
   * Wraps content_translation_delete_confirm().
   *
   * @todo Remove content_translation_delete_confirm().
   */
  public function deleteTranslation(Request $request, $language) {
    $entity = $request->attributes->get($request->attributes->get('_entity_type'));
    module_load_include('pages.inc', 'content_translation');
    $language = language_load($language);
    return drupal_get_form('content_translation_delete_confirm', $entity, $language);
  }

}
