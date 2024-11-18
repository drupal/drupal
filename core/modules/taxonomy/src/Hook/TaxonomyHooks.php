<?php

namespace Drupal\taxonomy\Hook;

use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for taxonomy.
 */
class TaxonomyHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.taxonomy':
        $field_ui_url = \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', ['name' => 'field_ui'])->toString() : '#';
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Taxonomy module allows users who have permission to create and edit content to categorize (tag) content of that type. Users who have the <em>Administer vocabularies and terms</em> <a href=":permissions" title="Taxonomy module permissions">permission</a> can add <em>vocabularies</em> that contain a set of related <em>terms</em>. The terms in a vocabulary can either be pre-set by an administrator or built gradually as content is added and edited. Terms may be organized hierarchically if desired.', [
          ':permissions' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'taxonomy',
          ])->toString(),
        ]) . '</p>';
        $output .= '<p>' . t('For more information, see the <a href=":taxonomy">online documentation for the Taxonomy module</a>.', [':taxonomy' => 'https://www.drupal.org/docs/8/core/modules/taxonomy']) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Managing vocabularies') . '</dt>';
        $output .= '<dd>' . t('Users who have the <em>Administer vocabularies and terms</em> permission can add and edit vocabularies from the <a href=":taxonomy_admin">Taxonomy administration page</a>. Vocabularies can be deleted from their <em>Edit vocabulary</em> page. Users with the <em>Taxonomy term: Administer fields</em> permission may add additional fields for terms in that vocabulary using the <a href=":field_ui">Field UI module</a>.', [
          ':taxonomy_admin' => Url::fromRoute('entity.taxonomy_vocabulary.collection')->toString(),
          ':field_ui' => $field_ui_url,
        ]) . '</dd>';
        $output .= '<dt>' . t('Managing terms') . '</dt>';
        $output .= '<dd>' . t('Users who have the <em>Administer vocabularies and terms</em> permission or the <em>Edit terms</em> permission for a particular vocabulary can add, edit, and organize the terms in a vocabulary from a vocabulary\'s term listing page, which can be accessed by going to the <a href=":taxonomy_admin">Taxonomy administration page</a> and clicking <em>List terms</em> in the <em>Operations</em> column. Users must have the <em>Administer vocabularies and terms</em> permission or the <em>Delete terms</em> permission for a particular vocabulary to delete terms.', [
          ':taxonomy_admin' => Url::fromRoute('entity.taxonomy_vocabulary.collection')->toString(),
        ]) . ' </dd>';
        $output .= '<dt>' . t('Classifying entity content') . '</dt>';
        $output .= '<dd>' . t('A user with the <em>Administer fields</em> permission for a certain entity type may add <em>Taxonomy term</em> reference fields to the entity type, which will allow entities to be classified using taxonomy terms. See the <a href=":entity_reference">Entity Reference help</a> for more information about reference fields. See the <a href=":field">Field module help</a> and the <a href=":field_ui">Field UI help</a> pages for general information on fields and how to create and manage them.', [
          ':field_ui' => $field_ui_url,
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':entity_reference' => Url::fromRoute('help.page', [
            'name' => 'entity_reference',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Adding new terms during content creation') . '</dt>';
        $output .= '<dd>' . t("Allowing users to add new terms gradually builds a vocabulary as content is added and edited. Users can add new terms if either of the two <em>Autocomplete</em> widgets is chosen for the Taxonomy term reference field in the <em>Manage form display</em> page for the field. You will also need to enable the <em>Create referenced entities if they don't already exist</em> option, and restrict the field to one vocabulary.") . '</dd>';
        $output .= '<dt>' . t('Configuring displays and form displays') . '</dt>';
        $output .= '<dd>' . t('See the <a href=":entity_reference">Entity Reference help</a> page for the field widgets and formatters that can be configured for any reference field on the <em>Manage display</em> and <em>Manage form display</em> pages. Taxonomy additionally provides an <em>RSS category</em> formatter that displays nothing when the entity item is displayed as HTML, but displays an RSS category instead of a list when the entity item is displayed in an RSS feed.', [
          ':entity_reference' => Url::fromRoute('help.page', [
            'name' => 'entity_reference',
          ])->toString(),
        ]) . '</li>';
        $output .= '</ul>';
        $output .= '</dd>';
        $output .= '</dl>';
        return $output;

      case 'entity.taxonomy_vocabulary.collection':
        $output = '<p>' . t('Taxonomy is for categorizing content. Terms are grouped into vocabularies. For example, a vocabulary called "Fruit" would contain the terms "Apple" and "Banana".') . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return ['taxonomy_term' => ['render element' => 'elements']];
  }

  /**
   * Implements hook_local_tasks_alter().
   *
   * @todo Evaluate removing as part of https://www.drupal.org/node/2358923.
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(&$local_tasks): void {
    $local_task_key = 'config_translation.local_tasks:entity.taxonomy_vocabulary.config_translation_overview';
    if (isset($local_tasks[$local_task_key])) {
      // The config_translation module expects the base route to be
      // entity.taxonomy_vocabulary.edit_form like it is for other configuration
      // entities. Taxonomy uses the overview_form as the base route.
      $local_tasks[$local_task_key]['base_route'] = 'entity.taxonomy_vocabulary.overview_form';
    }
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $term) {
    $operations = [];
    if ($term instanceof Term && $term->access('create')) {
      $operations['add-child'] = [
        'title' => t('Add child'),
        'weight' => 10,
        'url' => Url::fromRoute('entity.taxonomy_term.add_form', [
          'taxonomy_vocabulary' => $term->bundle(),
        ], [
          'query' => [
            'parent' => $term->id(),
          ],
        ]),
      ];
    }
    return $operations;
  }

  /**
   * @defgroup taxonomy_index Taxonomy indexing
   * @{
   * Functions to maintain taxonomy indexing.
   *
   * Taxonomy uses default field storage to store canonical relationships
   * between terms and fieldable entities. However its most common use case
   * requires listing all content associated with a term or group of terms
   * sorted by creation date. To avoid slow queries due to joining across
   * multiple node and field tables with various conditions and order by criteria,
   * we maintain a denormalized table with all relationships between terms,
   * published nodes and common sort criteria such as status, sticky and created.
   * When using other field storage engines or alternative methods of
   * denormalizing this data you should set the
   * taxonomy.settings:maintain_index_table to '0' to avoid unnecessary writes in
   * SQL.
   */

  /**
   * Implements hook_ENTITY_TYPE_insert() for node entities.
   */
  #[Hook('node_insert')]
  public function nodeInsert(EntityInterface $node) {
    // Add taxonomy index entries for the node.
    taxonomy_build_node_index($node);
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for node entities.
   */
  #[Hook('node_update')]
  public function nodeUpdate(EntityInterface $node) {
    // If we're not dealing with the default revision of the node, do not make any
    // change to the taxonomy index.
    if (!$node->isDefaultRevision()) {
      return;
    }
    taxonomy_delete_node_index($node);
    taxonomy_build_node_index($node);
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for node entities.
   */
  #[Hook('node_predelete')]
  public function nodePredelete(EntityInterface $node) {
    // Clean up the {taxonomy_index} table when nodes are deleted.
    taxonomy_delete_node_index($node);
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_delete')]
  public function taxonomyTermDelete(Term $term) {
    if (\Drupal::config('taxonomy.settings')->get('maintain_index_table')) {
      // Clean up the {taxonomy_index} table when terms are deleted.
      \Drupal::database()->delete('taxonomy_index')->condition('tid', $term->id())->execute();
    }
  }

}
