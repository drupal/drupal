<?php

namespace Drupal\taxonomy\Hook;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for taxonomy.
 */
class TaxonomyTokensHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $types['term'] = [
      'name' => $this->t("Taxonomy terms"),
      'description' => $this->t("Tokens related to taxonomy terms."),
      'needs-data' => 'term',
    ];
    $types['vocabulary'] = [
      'name' => $this->t("Vocabularies"),
      'description' => $this->t("Tokens related to taxonomy vocabularies."),
      'needs-data' => 'vocabulary',
    ];
    // Taxonomy term related variables.
    $term['tid'] = [
      'name' => $this->t("Term ID"),
      'description' => $this->t("The unique ID of the taxonomy term."),
    ];
    $term['uuid'] = ['name' => $this->t('UUID'), 'description' => $this->t("The UUID of the taxonomy term.")];
    $term['name'] = ['name' => $this->t("Name"), 'description' => $this->t("The name of the taxonomy term.")];
    $term['description'] = [
      'name' => $this->t("Description"),
      'description' => $this->t("The optional description of the taxonomy term."),
    ];
    $term['node-count'] = [
      'name' => $this->t("Node count"),
      'description' => $this->t("The number of nodes tagged with the taxonomy term."),
    ];
    $term['url'] = ['name' => $this->t("URL"), 'description' => $this->t("The URL of the taxonomy term.")];
    // Taxonomy vocabulary related variables.
    $vocabulary['vid'] = [
      'name' => $this->t("Vocabulary ID"),
      'description' => $this->t("The unique ID of the taxonomy vocabulary."),
    ];
    $vocabulary['name'] = ['name' => $this->t("Name"), 'description' => $this->t("The name of the taxonomy vocabulary.")];
    $vocabulary['description'] = [
      'name' => $this->t("Description"),
      'description' => $this->t("The optional description of the taxonomy vocabulary."),
    ];
    $vocabulary['node-count'] = [
      'name' => $this->t("Node count"),
      'description' => $this->t("The number of nodes tagged with terms belonging to the taxonomy vocabulary."),
    ];
    $vocabulary['term-count'] = [
      'name' => $this->t("Term count"),
      'description' => $this->t("The number of terms belonging to the taxonomy vocabulary."),
    ];
    // Chained tokens for taxonomies
    $term['vocabulary'] = [
      'name' => $this->t("Vocabulary"),
      'description' => $this->t("The vocabulary the taxonomy term belongs to."),
      'type' => 'vocabulary',
    ];
    $term['parent'] = [
      'name' => $this->t("Parent term"),
      'description' => $this->t("The parent term of the taxonomy term, if one exists."),
      'type' => 'term',
    ];
    $term['changed'] = [
      'name' => $this->t("Date changed"),
      'description' => $this->t("The date the taxonomy was most recently updated."),
      'type' => 'date',
    ];
    return ['types' => $types, 'tokens' => ['term' => $term, 'vocabulary' => $vocabulary]];
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    $token_service = \Drupal::token();
    if (isset($options['langcode'])) {
      $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
      $langcode = $options['langcode'];
    }
    else {
      $langcode = LanguageInterface::LANGCODE_DEFAULT;
    }
    $replacements = [];
    if ($type == 'term' && !empty($data['term'])) {
      $term = $data['term'];
      $term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $options['langcode'] ?? NULL);
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'tid':
            $replacements[$original] = $term->id();
            break;

          case 'uuid':
            $replacements[$original] = $term->uuid();
            break;

          case 'name':
            $replacements[$original] = $term->label();
            break;

          case 'description':
            // "processed" returns a \Drupal\Component\Render\MarkupInterface
            // via check_markup().
            $replacements[$original] = $term->description->processed;
            break;

          case 'url':
            $replacements[$original] = $term->toUrl('canonical', ['absolute' => TRUE])->toString();
            break;

          case 'node-count':
            $query = \Drupal::database()->select('taxonomy_index');
            $query->condition('tid', $term->id());
            $query->addTag('term_node_count');
            $count = $query->countQuery()->execute()->fetchField();
            $replacements[$original] = $count;
            break;

          case 'vocabulary':
            $vocabulary = Vocabulary::load($term->bundle());
            $bubbleable_metadata->addCacheableDependency($vocabulary);
            $replacements[$original] = $vocabulary->label();
            break;

          case 'parent':
            $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
            if ($parents = $taxonomy_storage->loadParents($term->id())) {
              $parent = array_pop($parents);
              $bubbleable_metadata->addCacheableDependency($parent);
              $replacements[$original] = $parent->getName();
            }
            break;

          case 'changed':
            $date_format = DateFormat::load('medium');
            $bubbleable_metadata->addCacheableDependency($date_format);
            $replacements[$original] = \Drupal::service('date.formatter')->format($term->getChangedTime(), 'medium', '', NULL, $langcode);
            break;
        }
      }
      if ($vocabulary_tokens = $token_service->findWithPrefix($tokens, 'vocabulary')) {
        $vocabulary = Vocabulary::load($term->bundle());
        $replacements += $token_service->generate('vocabulary', $vocabulary_tokens, ['vocabulary' => $vocabulary], $options, $bubbleable_metadata);
      }
      if ($vocabulary_tokens = $token_service->findWithPrefix($tokens, 'parent')) {
        $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        if ($parents = $taxonomy_storage->loadParents($term->id())) {
          $parent = array_pop($parents);
          $replacements += $token_service->generate('term', $vocabulary_tokens, ['term' => $parent], $options, $bubbleable_metadata);
        }
      }
      if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
        $replacements += $token_service->generate('date', $changed_tokens, ['date' => $term->getChangedTime()], $options, $bubbleable_metadata);
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
            $replacements[$original] = $vocabulary->label();
            break;

          case 'description':
            $build = ['#markup' => $vocabulary->getDescription()];
            // @todo Fix in https://www.drupal.org/node/2577827
            $replacements[$original] = \Drupal::service('renderer')->renderInIsolation($build);
            break;

          case 'term-count':
            $replacements[$original] = \Drupal::entityQuery('taxonomy_term')->accessCheck(TRUE)->condition('vid', $vocabulary->id())->addTag('vocabulary_term_count')->count()->execute();
            break;

          case 'node-count':
            $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
            $replacements[$original] = $taxonomy_storage->nodeCount($vocabulary->id());
            break;
        }
      }
    }
    return $replacements;
  }

}
