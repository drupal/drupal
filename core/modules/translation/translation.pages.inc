<?php

/**
 * @file
 * User page callbacks for the Translation module.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;

/**
 * Page callback: Displays a list of a node's translations.
 *
 * @param \Drupal\Core\Entity\EntityInterface $node
 *   A node entity.
 *
 * @return
 *   A render array for a page containing a list of content.
 *
 * @see translation_menu()
 *
 * @deprecated Use \Drupal\translation\Controller\TranslationController::nodeOverview()
 */
function translation_node_overview(EntityInterface $node) {
  include_once DRUPAL_ROOT . '/core/includes/language.inc';

  if ($node->tnid->value) {
    // Already part of a set, grab that set.
    $tnid = $node->tnid->value;
    $translations = translation_node_get_translations($node->tnid->value);
  }
  else {
    // We have no translation source nid, this could be a new set, emulate that.
    $tnid = $node->id();
    $translations = array($node->language()->id => $node);
  }

  $type = \Drupal::config('translation.settings')->get('language_type');
  $header = array(t('Language'), t('Title'), t('Status'), t('Operations'));

  foreach (language_list() as $langcode => $language) {
    $options = array();
    $language_name = $language->name;
    if (isset($translations[$langcode])) {
      // Existing translation in the translation set: display status.
      // We load the full node to check whether the user can edit it.
      $nid = $translations[$langcode] instanceof EntityInterface ? $translations[$langcode]->id() : $translations[$langcode]->nid;
      $translation_node = node_load($nid);
      $path = 'node/' . $translation_node->id();
      $links = language_negotiation_get_switch_links($type, $path);
      $title = empty($links->links[$langcode]['href']) ? l($translation_node->label(), $path) : l($translation_node->label(), $links->links[$langcode]['href'], $links->links[$langcode]);
      if (node_access('update', $translation_node)) {
        $path = 'node/' . $translation_node->id() . '/edit';
        $links = language_negotiation_get_switch_links($type, $path);
        if (!empty($links->links[$langcode]['href'])) {
          $options['edit'] = array(
            'title' => t('edit'),
          ) + $links->links[$langcode];
        }
      }
      $status = $translation_node->isPublished() ? t('Published') : t('Not published');
      $status .= $translation_node->translate->value ? ' - <span class="marker">' . t('outdated') . '</span>' : '';
      if ($translation_node->id() == $tnid) {
        $language_name = t('<strong>@language_name</strong> (source)', array('@language_name' => $language_name));
      }
    }
    else {
      // No such translation in the set yet: help user to create it.
      $title = t('n/a');
      if (node_access('create', $node)) {
        $path = 'node/add/' . $node->getType();
        $links = language_negotiation_get_switch_links($type, $path);
        $query = array('query' => array('translation' => $node->id(), 'target' => $langcode));
        if (!empty($links->links[$langcode]['href'])) {
          $options['add'] = array(
            'title' => t('Add translation'),
          ) + $links->links[$langcode];
          $options['add'] = NestedArray::mergeDeep($options['add'], $query);
        }
      }
      $status = t('Not translated');
    }
    $row = array();
    $row[] = $language_name;
    $row[] = $title;
    $row[] = $status;
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $options,
      ),
    );
    $rows[] = $row;
  }

  $build['#title'] = t('Translations of %title', array('%title' => $node->label()));

  $build['translation_node_overview'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  return $build;
}
