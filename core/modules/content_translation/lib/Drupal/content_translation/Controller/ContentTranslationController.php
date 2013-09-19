<?php

/**
 * @file
 * Contains \Drupal\content_translation\Controller\ContentTranslationController.
 */

namespace Drupal\content_translation\Controller;

use Drupal\Core\Entity\EntityInterface;

/**
 * Base class for entity translation controllers.
 */
class ContentTranslationController {

  /**
   * @todo Remove content_translation_overview().
   */
  public function overview(EntityInterface $entity) {
    module_load_include('pages.inc', 'content_translation');
    return content_translation_overview($entity);
  }

  /**
   * @todo Remove content_translation_add_page().
   */
  public function add(EntityInterface $entity, $source, $target) {
    module_load_include('pages.inc', 'content_translation');
    $source = language_load($source);
    $target = language_load($target);
    return content_translation_add_page($entity, $source, $target);
  }

  /**
   * @todo Remove content_translation_edit_page().
   */
  public function edit(EntityInterface $entity, $language) {
    module_load_include('pages.inc', 'content_translation');
    $language = language_load($language);
    return content_translation_edit_page($entity, $language);
  }

}
