<?php
// $Id: translation.pages.inc,v 1.18 2010/12/31 20:45:25 webchick Exp $

/**
 * @file
 * User page callbacks for the translation module.
 */

/**
 * Overview page for a node's translations.
 *
 * @param $node
 *   Node object.
 */
function translation_node_overview($node) {
  include_once DRUPAL_ROOT . '/includes/language.inc';

  if ($node->tnid) {
    // Already part of a set, grab that set.
    $tnid = $node->tnid;
    $translations = translation_node_get_translations($node->tnid);
  }
  else {
    // We have no translation source nid, this could be a new set, emulate that.
    $tnid = $node->nid;
    $translations = array($node->language => $node);
  }

  $type = variable_get('translation_language_type', LANGUAGE_TYPE_INTERFACE);
  $header = array(t('Language'), t('Title'), t('Status'), t('Operations'));

  foreach (language_list() as $langcode => $language) {
    $options = array();
    $language_name = $language->name;
    if (isset($translations[$langcode])) {
      // Existing translation in the translation set: display status.
      // We load the full node to check whether the user can edit it.
      $translation_node = node_load($translations[$langcode]->nid);
      $path = 'node/' . $translation_node->nid;
      $links = language_negotiation_get_switch_links($type, $path);
      $title = empty($links->links[$langcode]) ? l($translation_node->title, $path) : l($translation_node->title, $links->links[$langcode]['href'], $links->links[$langcode]);
      if (node_access('update', $translation_node)) {
        $text = t('edit');
        $path = 'node/' . $translation_node->nid . '/edit';
        $links = language_negotiation_get_switch_links($type, $path);
        $options[] = empty($links->links[$langcode]) ? l($text, $path) : l($text, $links->links[$langcode]['href'], $links->links[$langcode]);
      }
      $status = $translation_node->status ? t('Published') : t('Not published');
      $status .= $translation_node->translate ? ' - <span class="marker">' . t('outdated') . '</span>' : '';
      if ($translation_node->nid == $tnid) {
        $language_name = t('<strong>@language_name</strong> (source)', array('@language_name' => $language_name));
      }
    }
    else {
      // No such translation in the set yet: help user to create it.
      $title = t('n/a');
      if (node_access('create', $node)) {
        $text = t('add translation');
        $path = 'node/add/' . str_replace('_', '-', $node->type);
        $links = language_negotiation_get_switch_links($type, $path);
        $query = array('query' => array('translation' => $node->nid, 'target' => $langcode));
        $options[] = empty($links->links[$langcode]) ? l($text, $path, $query) : l($text, $links->links[$langcode]['href'], array_merge_recursive($links->links[$langcode], $query));
      }
      $status = t('Not translated');
    }
    $rows[] = array($language_name, $title, $status, implode(" | ", $options));
  }

  drupal_set_title(t('Translations of %title', array('%title' => $node->title)), PASS_THROUGH);

  $build['translation_node_overview'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  return $build;
}
