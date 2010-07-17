<?php
// $Id: translation.pages.inc,v 1.16 2010/07/17 11:31:36 dries Exp $

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

  $header = array(t('Language'), t('Title'), t('Status'), t('Operations'));

  foreach (language_list() as $language) {
    $options = array();
    $language_name = $language->name;
    if (isset($translations[$language->language])) {
      // Existing translation in the translation set: display status.
      // We load the full node to check whether the user can edit it.
      $translation_node = node_load($translations[$language->language]->nid);
      $title = l($translation_node->title, 'node/' . $translation_node->nid);
      if (node_access('update', $translation_node)) {
        $options[] = l(t('edit'), "node/$translation_node->nid/edit");
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
        $options[] = l(t('add translation'), 'node/add/' . str_replace('_', '-', $node->type), array('query' => array('translation' => $node->nid, 'target' => $language->language)));
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
