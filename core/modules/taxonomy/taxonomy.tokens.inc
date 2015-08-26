<?php

/**
 * @file
 * Builds placeholder replacement tokens for taxonomy terms and vocabularies.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Implements hook_token_info().
 */
function taxonomy_token_info() {
  $types['term'] = array(
    'name' => t("Taxonomy terms"),
    'description' => t("Tokens related to taxonomy terms."),
    'needs-data' => 'term',
  );
  $types['vocabulary'] = array(
    'name' => t("Vocabularies"),
    'description' => t("Tokens related to taxonomy vocabularies."),
    'needs-data' => 'vocabulary',
  );

  // Taxonomy term related variables.
  $term['tid'] = array(
    'name' => t("Term ID"),
    'description' => t("The unique ID of the taxonomy term."),
  );
  $term['name'] = array(
    'name' => t("Name"),
    'description' => t("The name of the taxonomy term."),
  );
  $term['description'] = array(
    'name' => t("Description"),
    'description' => t("The optional description of the taxonomy term."),
  );
  $term['node-count'] = array(
    'name' => t("Node count"),
    'description' => t("The number of nodes tagged with the taxonomy term."),
  );
  $term['url'] = array(
    'name' => t("URL"),
    'description' => t("The URL of the taxonomy term."),
  );

  // Taxonomy vocabulary related variables.
  $vocabulary['vid'] = array(
    'name' => t("Vocabulary ID"),
    'description' => t("The unique ID of the taxonomy vocabulary."),
  );
  $vocabulary['name'] = array(
    'name' => t("Name"),
    'description' => t("The name of the taxonomy vocabulary."),
  );
  $vocabulary['description'] = array(
    'name' => t("Description"),
    'description' => t("The optional description of the taxonomy vocabulary."),
  );
  $vocabulary['node-count'] = array(
    'name' => t("Node count"),
    'description' => t("The number of nodes tagged with terms belonging to the taxonomy vocabulary."),
  );
  $vocabulary['term-count'] = array(
    'name' => t("Term count"),
    'description' => t("The number of terms belonging to the taxonomy vocabulary."),
  );

  // Chained tokens for taxonomies
  $term['vocabulary'] = array(
    'name' => t("Vocabulary"),
    'description' => t("The vocabulary the taxonomy term belongs to."),
    'type' => 'vocabulary',
  );
  $term['parent'] = array(
    'name' => t("Parent term"),
    'description' => t("The parent term of the taxonomy term, if one exists."),
    'type' => 'term',
  );

  return array(
    'types' => $types,
    'tokens' => array(
      'term' => $term,
      'vocabulary' => $vocabulary,
    ),
  );
}

/**
 * Implements hook_tokens().
 */
function taxonomy_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = array();
  $sanitize = !empty($options['sanitize']);
  $taxonomy_storage = \Drupal::entityManager()->getStorage('taxonomy_term');
  if ($type == 'term' && !empty($data['term'])) {
    $term = $data['term'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'tid':
          $replacements[$original] = $term->id();
          break;

        case 'name':
          $replacements[$original] = $sanitize ? Html::escape($term->getName()) : $term->getName();
          break;

        case 'description':
          $replacements[$original] = $sanitize ? $term->description->processed : $term->getDescription();
          break;

        case 'url':
          $replacements[$original] = $term->url('canonical', array('absolute' => TRUE));
          break;

        case 'node-count':
          $query = db_select('taxonomy_index');
          $query->condition('tid', $term->id());
          $query->addTag('term_node_count');
          $count = $query->countQuery()->execute()->fetchField();
          $replacements[$original] = $count;
          break;

        case 'vocabulary':
          $vocabulary = Vocabulary::load($term->bundle());
          $bubbleable_metadata->addCacheableDependency($vocabulary);
          $replacements[$original] = Html::escape($vocabulary->label());
          break;

        case 'parent':
          if ($parents = $taxonomy_storage->loadParents($term->id())) {
            $parent = array_pop($parents);
            $bubbleable_metadata->addCacheableDependency($parent);
            $replacements[$original] = Html::escape($parent->getName());
          }
          break;
      }
    }

    if ($vocabulary_tokens = $token_service->findWithPrefix($tokens, 'vocabulary')) {
      $vocabulary = Vocabulary::load($term->bundle());
      $replacements += $token_service->generate('vocabulary', $vocabulary_tokens, array('vocabulary' => $vocabulary), $options, $bubbleable_metadata);
    }

    if (($vocabulary_tokens = $token_service->findWithPrefix($tokens, 'parent')) && $parents = $taxonomy_storage->loadParents($term->id())) {
      $parent = array_pop($parents);
      $replacements += $token_service->generate('term', $vocabulary_tokens, array('term' => $parent), $options, $bubbleable_metadata);
    }
  }

  elseif ($type == 'vocabulary' && !empty($data['vocabulary'])) {
    $vocabulary = $data['vocabulary'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'vid':
          $replacements[$original] = $vocabulary->id();
          break;

        case 'name':
          $replacements[$original] = $sanitize ? Html::escape($vocabulary->label()) : $vocabulary->label();
          break;

        case 'description':
          $replacements[$original] = $sanitize ? Xss::filter($vocabulary->getDescription()) : $vocabulary->getDescription();
          break;

        case 'term-count':
          $replacements[$original] = \Drupal::entityQuery('taxonomy_term')
            ->condition('vid', $vocabulary->id())
            ->addTag('vocabulary_term_count')
            ->count()
            ->execute();
          break;

        case 'node-count':
          $replacements[$original] = $taxonomy_storage->nodeCount($vocabulary->id());
          break;
      }
    }
  }

  return $replacements;
}
