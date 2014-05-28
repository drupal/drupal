<?php

/**
 * @file
 * Contains \Drupal\content_translation\Controller\ContentTranslationController.
 */

namespace Drupal\content_translation\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for entity translation controllers.
 */
class ContentTranslationController {

  /**
   * @todo Remove content_translation_overview().
   */
  public function overview(Request $request) {
    $entity = $request->attributes->get($request->attributes->get('_entity_type_id'));
    module_load_include('pages.inc', 'content_translation');
    return content_translation_overview($entity);
  }

  /**
   * @todo Remove content_translation_add_page().
   */
  public function add(Request $request, $source, $target) {
    $entity = $request->attributes->get($request->attributes->get('_entity_type_id'));
    module_load_include('pages.inc', 'content_translation');
    $source = language_load($source);
    $target = language_load($target);
    return content_translation_add_page($entity, $source, $target);
  }

  /**
   * @todo Remove content_translation_edit_page().
   */
  public function edit(Request $request, $language) {
    $entity = $request->attributes->get($request->attributes->get('_entity_type_id'));
    module_load_include('pages.inc', 'content_translation');
    $language = language_load($language);
    return content_translation_edit_page($entity, $language);
  }

}
