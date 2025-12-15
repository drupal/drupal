<?php

namespace Drupal\block_content\Hook;

use Drupal\block\BlockConfigUpdater;
use Drupal\block\BlockInterface;
use Drupal\block_content\Plugin\EntityReferenceSelection\BlockContentSelection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for block_content.
 */
class BlockContentHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.block_content':
        $field_ui = \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', ['name' => 'field_ui'])->toString() : '#';
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Block Content module manages the creation, editing, and deletion of content blocks. Content blocks are field-able content entities managed by the <a href=":field">Field module</a>. For more information, see the <a href=":block-content">online documentation for the Block Content module</a>.', [
          ':block-content' => 'https://www.drupal.org/documentation/modules/block_content',
          ':field' => Url::fromRoute('help.page', ['name' => 'field'])->toString(),
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Creating and managing block types') . '</dt>';
        $output .= '<dd>' . $this->t('Users with the <em>Administer block types</em> permission can create and edit block types with fields and display settings, from the <a href=":types">Block types</a> page under the Structure menu. For more information about managing fields and display settings, see the <a href=":field-ui">Field UI module help</a> and <a href=":field">Field module help</a>.', [
          ':types' => Url::fromRoute('entity.block_content_type.collection')->toString(),
          ':field-ui' => $field_ui,
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Creating content blocks') . '</dt>';
        $output .= '<dd>' . $this->t('Users with the <em>Administer block content</em> or <em>Create new content block</em> permissions for an individual block type are able to add content blocks. These can be created on the <a href=":add-content-block">Add content block page</a> or on the <em>Place block</em> modal on the <a href=":block-layout">Block Layout page</a> and are reusable across the entire site. Content blocks created in Layout Builder for a content type or individual node layouts are not reusable and also called inline blocks.', [
          ':add-content-block' => Url::fromRoute('block_content.add_page')->toString(),
          ':block-layout' => Url::fromRoute('block.admin_display')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    return [
      'block_content_add_list' => [
        'variables' => [
          'content' => NULL,
        ],
        'deprecated' => 'The "block_content_add_list" template is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use "entity_add_list" instead. See https://www.drupal.org/node/3530643.',
      ],
    ];
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    // Add a translation handler for fields if the language module is enabled.
    if (\Drupal::moduleHandler()->moduleExists('language')) {
      $translation = $entity_types['block_content']->get('translation');
      $translation['block_content'] = TRUE;
      $entity_types['block_content']->set('translation', $translation);
    }
    // Swap out the default EntityChanged constraint with a custom one with
    // different logic for inline blocks.
    $constraints = $entity_types['block_content']->getConstraints();
    unset($constraints['EntityChanged']);
    $constraints['BlockContentEntityChanged'] = NULL;
    $entity_types['block_content']->setConstraints($constraints);
  }

  /**
   * Implements hook_query_TAG_alter().
   *
   * Alters any 'entity_reference' query where the entity type is
   * 'block_content' and the query has the tag 'block_content_access'.
   *
   * These queries should only return reusable blocks unless a condition on
   * 'reusable' is explicitly set.
   *
   * Block_content entities that are not reusable should by default not be
   * selectable as entity reference values. A module can still create an
   * instance of \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   * that will allow selection of non-reusable blocks by explicitly setting a
   * condition on the 'reusable' field.
   *
   * @see \Drupal\block_content\BlockContentAccessControlHandler
   */
  #[Hook('query_entity_reference_alter')]
  public function queryEntityReferenceAlter(AlterableInterface $query): void {
    if (($query->alterMetaData['entity_reference_selection_handler'] ?? NULL) instanceof BlockContentSelection) {
      // The entity reference selection plugin module provided by this module
      // already filters out non-reusable blocks so no altering of the query is
      // needed.
      return;
    }
    if ($query instanceof SelectInterface && $query->getMetaData('entity_type') === 'block_content' && $query->hasTag('block_content_access')) {
      $data_table = \Drupal::entityTypeManager()->getDefinition('block_content')->getDataTable();
      if (array_key_exists($data_table, $query->getTables()) && !_block_content_has_reusable_condition($query->conditions(), $query->getTables())) {
        @trigger_error('Automatically filtering block_content entity reference selection queries to only reusable blocks is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Either add the condition manually in buildEntityQuery, or extend \Drupal\block_content\Plugin\EntityReferenceSelection\BlockContentSelection. See https://www.drupal.org/node/3521459', E_USER_DEPRECATED);
        $query->condition("{$data_table}.reusable", TRUE);
      }
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for block templates.
   */
  #[Hook('theme_suggestions_block_alter')]
  public function themeSuggestionsBlockAlter(array &$suggestions, array $variables): void {
    $suggestions_new = [];
    $content = $variables['elements']['content'];
    $block_content = $variables['elements']['content']['#block_content'] ?? NULL;
    if ($block_content instanceof BlockContentInterface) {
      $bundle = $content['#block_content']->bundle();
      $view_mode = strtr($variables['elements']['content']['#view_mode'], '.', '_');
      $suggestions_new[] = 'block__block_content__view__' . $view_mode;
      $suggestions_new[] = 'block__block_content__type__' . $bundle;
      $suggestions_new[] = 'block__block_content__view_type__' . $bundle . '__' . $view_mode;
      if (!empty($variables['elements']['#id'])) {
        $suggestions_new[] = 'block__block_content__id__' . $variables['elements']['#id'];
        $suggestions_new[] = 'block__block_content__id_view__' . $variables['elements']['#id'] . '__' . $view_mode;
      }
      // Remove duplicate block__block_content.
      $suggestions = array_unique($suggestions);
      array_splice($suggestions, 1, 0, $suggestions_new);
    }
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity) : array {
    $operations = [];
    if ($entity instanceof BlockInterface) {
      $plugin = $entity->getPlugin();
      if ($plugin->getBaseId() === 'block_content') {
        $custom_block = \Drupal::entityTypeManager()->getStorage('block_content')->loadByProperties(['uuid' => $plugin->getDerivativeId()]);
        $custom_block = reset($custom_block);
        if ($custom_block && $custom_block->access('update')) {
          $operations['block-edit'] = [
            'title' => $this->t('Edit block'),
            'url' => $custom_block->toUrl('edit-form')->setOptions([]),
            'weight' => 50,
          ];
        }
      }
    }
    return $operations;
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('block_presave')]
  public function blockPreSave(BlockInterface $block): void {
    // Use an inline service since DI would require enabling the block module
    // in any Kernel test that installs block_content. This is BC code so will
    // be removed in Drupal 12 anyway.
    \Drupal::service(BlockConfigUpdater::class)->updateBlock($block);
  }

}
