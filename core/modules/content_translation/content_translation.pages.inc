<?php

/**
 * @file
 * The content translation user interface.
 */

use Drupal\Core\Language\Language;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Translations overview page callback.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity whose translation overview should be displayed.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\content_translation\Controller\ContentTranslationController::overview().
 */
function content_translation_overview(EntityInterface $entity) {
  $controller = content_translation_controller($entity->getEntityTypeId());
  $languages = language_list();
  $original = $entity->getUntranslated()->language()->id;
  $translations = $entity->getTranslationLanguages();
  $administrator = \Drupal::currentUser()->hasPermission('administer languages');

  $rel = array();
  foreach (array('canonical', 'edit-form', 'drupal:content-translation-overview') as $name) {
    $rel[$name] = $entity->getSystemPath($name);
  }

  $header = array(t('Language'), t('Translation'), t('Source language'), t('Status'), t('Operations'));
  $rows = array();

  if (\Drupal::languageManager()->isMultilingual()) {

    // Determine whether the current entity is translatable.
    $translatable = FALSE;
    foreach (field_info_instances($entity->getEntityTypeId(), $entity->bundle()) as $instance) {
      if ($instance->isTranslatable()) {
        $translatable = TRUE;
        break;
      }
    }

    foreach ($languages as $language) {
      $language_name = $language->name;
      $langcode = $language->id;

      $add_path = $rel['drupal:content-translation-overview'] . '/add/' . $original . '/' . $langcode;
      $translate_path = $rel['drupal:content-translation-overview'] . '/edit/' . $langcode;

      $add_links = _content_translation_get_switch_links($add_path);
      $edit_links = _content_translation_get_switch_links($rel['edit-form']);
      $translate_links = _content_translation_get_switch_links($translate_path);
      $delete_links = _content_translation_get_switch_links($rel['drupal:content-translation-overview'] . '/delete/' . $langcode);

      $operations = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => array(),
        ),
      );
      $links = &$operations['data']['#links'];

      if (isset($translations[$langcode])) {
        // Existing translation in the translation set: display status.
        $source = isset($entity->translation[$langcode]['source']) ? $entity->translation[$langcode]['source'] : '';
        $is_original = $langcode == $original;
        $label = $entity->getTranslation($langcode)->label();
        $link = isset($links->links[$langcode]['href']) ? $links->links[$langcode] : array('href' => $rel['canonical'], 'language' => $language);
        $row_title = l($label, $link['href'], $link);

        if (empty($link['href'])) {
          $row_title = $is_original ? $label : t('n/a');
        }

        // If the user is allowed to edit the entity we point the edit link to
        // the entity form, otherwise if we are not dealing with the original
        // language we point the link to the translation form.
        if ($entity->access('update')) {
          $links['edit'] = isset($edit_links->links[$langcode]['href']) ? $edit_links->links[$langcode] : array('href' => $rel['edit-form'], 'language' => $language);
        }
        elseif (!$is_original && $controller->getTranslationAccess($entity, 'update')) {
          $links['edit'] = isset($translate_links->links[$langcode]['href']) ? $translate_links->links[$langcode] : array('href' => $translate_path, 'language' => $language);
        }

        if (isset($links['edit'])) {
          $links['edit']['title'] = t('Edit');
        }

        $translation = $entity->translation[$langcode];
        $status = !empty($translation['status']) ? t('Published') : t('Not published');
        // @todo Add a theming function here.
        $status = '<span class="status">' . $status . '</span>' . (!empty($translation['outdated']) ? ' <span class="marker">' . t('outdated') . '</span>' : '');

        if ($is_original) {
          $language_name = t('<strong>@language_name (Original language)</strong>', array('@language_name' => $language_name));
          $source_name = t('n/a');
        }
        else {
          $source_name = isset($languages[$source]) ? $languages[$source]->name : t('n/a');
          if ($controller->getTranslationAccess($entity, 'delete')) {
            $links['delete'] = isset($delete_links->links[$langcode]['href']) ? $delete_links->links[$langcode] : array('href' => $delete_links, 'language' => $language);
            $links['delete']['title'] = t('Delete');
          }
        }
      }
      else {
        // No such translation in the set yet: help user to create it.
        $row_title = $source_name = t('n/a');
        $source = $entity->language()->id;

        if ($source != $langcode && $controller->getTranslationAccess($entity, 'create')) {
          if ($translatable) {
            $links['add'] = isset($add_links->links[$langcode]['href']) ? $add_links->links[$langcode] : array('href' => $add_path, 'language' => $language);
            $links['add']['title'] = t('Add');
          }
          elseif ($administrator) {
            $links['nofields'] = array('title' => t('No translatable fields'), 'route_name' => 'language.content_settings_page', 'language' => $language);
          }
        }

        $status = t('Not translated');
      }

      $rows[] = array($language_name, $row_title, $source_name, $status, $operations);
    }
  }

  $build['#title'] = t('Translations of %label', array('%label' => $entity->label()));

  // Add metadata to the build render array to let other modules know about
  // which entity this is.
  $build['#entity'] = $entity;

  $build['content_translation_overview'] = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  return $build;
}

/**
 * Returns the localized links for the given path.
 *
 * @param string $path
 *   The path for which language switch links should be provided.
 *
 * @returns
 *   A renderable array of language switch links.
 */
function _content_translation_get_switch_links($path) {
  $links = \Drupal::languageManager()->getLanguageSwitchLinks(Language::TYPE_CONTENT, $path);
  if (empty($links)) {
    // If content language is set up to fall back to the interface language,
    // then there will be no switch links for Language::TYPE_CONTENT, ergo we
    // also need to use interface switch links.
    $links = \Drupal::languageManager()->getLanguageSwitchLinks(Language::TYPE_INTERFACE, $path);
  }
  return $links;
}

/**
 * Page callback for the translation addition page.
 *
 * @param EntityInterface $entity
 *   The entity being translated.
 * @param Language $source
 *   (optional) The language of the values being translated. Defaults to the
 *   entity language.
 * @param Language $target
 *   (optional) The language of the translated values. Defaults to the current
 *   content language.
 *
 * @return array
 *   A processed form array ready to be rendered.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\content_translation\Controller\ContentTranslationController::add().
 */
function content_translation_add_page(EntityInterface $entity, Language $source = NULL, Language $target = NULL) {
  $source = !empty($source) ? $source : $entity->language();
  $target = !empty($target) ? $target : \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_CONTENT);
  // @todo Exploit the upcoming hook_entity_prepare() when available.
  content_translation_prepare_translation($entity, $source, $target);
  $form_state['langcode'] = $target->id;
  $form_state['content_translation']['source'] = $source;
  $form_state['content_translation']['target'] = $target;
  $form_state['content_translation']['translation_form'] = !$entity->access('update');
  return \Drupal::service('entity.form_builder')->getForm($entity, 'default', $form_state);
}

/**
 * Page callback for the translation edit page.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being translated.
 * @param \Drupal\Core\Language\Language $language
 *   (optional) The language of the translated values. Defaults to the current
 *   content language.
 *
 * @return array
 *   A processed form array ready to be rendered.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\content_translation\Controller\ContentTranslationController::edit().
 */
function content_translation_edit_page(EntityInterface $entity, Language $language = NULL) {
  $language = !empty($language) ? $language : \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_CONTENT);
  $form_state['langcode'] = $language->id;
  $form_state['content_translation']['translation_form'] = TRUE;
  return \Drupal::service('entity.form_builder')->getForm($entity, 'default', $form_state);
}

/**
 * Populates target values with the source values.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entitiy being translated.
 * @param \Drupal\Core\Language\Language $source
 *   The language to be used as source.
 * @param \Drupal\Core\Language\Language $target
 *   The language to be used as target.
 */
function content_translation_prepare_translation(EntityInterface $entity, Language $source, Language $target) {
  if ($entity instanceof ContentEntityInterface) {
    $source_translation = $entity->getTranslation($source->id);
    $entity->addTranslation($target->id, $source_translation->getPropertyValues());
  }
}
