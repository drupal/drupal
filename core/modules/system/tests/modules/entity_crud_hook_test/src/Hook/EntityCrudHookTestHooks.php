<?php

declare(strict_types=1);

namespace Drupal\entity_crud_hook_test\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_crud_hook_test.
 */
class EntityCrudHookTestHooks {

  /**
   * Implements hook_entity_create().
   */
  #[Hook('entity_create')]
  public function entityCreate(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_create called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for block entities.
   */
  #[Hook('block_create')]
  public function blockCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for comment entities.
   */
  #[Hook('comment_create')]
  public function commentCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for file entities.
   */
  #[Hook('file_create')]
  public function fileCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for node entities.
   */
  #[Hook('node_create')]
  public function nodeCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_create')]
  public function taxonomyTermCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_create')]
  public function taxonomyVocabularyCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_create called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for user entities.
   */
  #[Hook('user_create')]
  public function userCreate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_create called';
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_presave called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for block entities.
   */
  #[Hook('block_presave')]
  public function blockPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for comment entities.
   */
  #[Hook('comment_presave')]
  public function commentPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for file entities.
   */
  #[Hook('file_presave')]
  public function filePresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for node entities.
   */
  #[Hook('node_presave')]
  public function nodePresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_presave')]
  public function taxonomyTermPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_presave')]
  public function taxonomyVocabularyPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_presave called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for user entities.
   */
  #[Hook('user_presave')]
  public function userPresave(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_presave called';
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_insert called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for block entities.
   */
  #[Hook('block_insert')]
  public function blockInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for comment entities.
   */
  #[Hook('comment_insert')]
  public function commentInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for file entities.
   */
  #[Hook('file_insert')]
  public function fileInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for node entities.
   */
  #[Hook('node_insert')]
  public function nodeInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_insert')]
  public function taxonomyTermInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_insert')]
  public function taxonomyVocabularyInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_insert called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for user entities.
   */
  #[Hook('user_insert')]
  public function userInsert(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_insert called';
  }

  /**
   * Implements hook_entity_preload().
   */
  #[Hook('entity_preload')]
  public function entityPreload(array $entities, $type): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_preload called for type ' . $type;
  }

  /**
   * Implements hook_entity_load().
   */
  #[Hook('entity_load')]
  public function entityLoad(array $entities, $type): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_load called for type ' . $type;
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for block entities.
   */
  #[Hook('block_load')]
  public function blockLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for comment entities.
   */
  #[Hook('comment_load')]
  public function commentLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for file entities.
   */
  #[Hook('file_load')]
  public function fileLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for node entities.
   */
  #[Hook('node_load')]
  public function nodeLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_load')]
  public function taxonomyTermLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_load')]
  public function taxonomyVocabularyLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_load called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for user entities.
   */
  #[Hook('user_load')]
  public function userLoad(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_load called';
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_update called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for block entities.
   */
  #[Hook('block_update')]
  public function blockUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for comment entities.
   */
  #[Hook('comment_update')]
  public function commentUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for file entities.
   */
  #[Hook('file_update')]
  public function fileUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for node entities.
   */
  #[Hook('node_update')]
  public function nodeUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_update')]
  public function taxonomyTermUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_update')]
  public function taxonomyVocabularyUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_update called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for user entities.
   */
  #[Hook('user_update')]
  public function userUpdate(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_update called';
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_predelete called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for block entities.
   */
  #[Hook('block_predelete')]
  public function blockPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for comment entities.
   */
  #[Hook('comment_predelete')]
  public function commentPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for file entities.
   */
  #[Hook('file_predelete')]
  public function filePredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for node entities.
   */
  #[Hook('node_predelete')]
  public function nodePredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_predelete')]
  public function taxonomyTermPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_predelete')]
  public function taxonomyVocabularyPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_predelete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for user entities.
   */
  #[Hook('user_predelete')]
  public function userPredelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_predelete called';
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_delete called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for block entities.
   */
  #[Hook('block_delete')]
  public function blockDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for comment entities.
   */
  #[Hook('comment_delete')]
  public function commentDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for file entities.
   */
  #[Hook('file_delete')]
  public function fileDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for node entities.
   */
  #[Hook('node_delete')]
  public function nodeDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_delete')]
  public function taxonomyTermDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_delete')]
  public function taxonomyVocabularyDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_delete called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for user entities.
   */
  #[Hook('user_delete')]
  public function userDelete(): void {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_delete called';
  }

}
