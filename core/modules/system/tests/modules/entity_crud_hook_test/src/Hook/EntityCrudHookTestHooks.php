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
  public function entityCreate(EntityInterface $entity) {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_create' . ' called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for block entities.
   */
  #[Hook('block_create')]
  public function blockCreate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_create' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for comment entities.
   */
  #[Hook('comment_create')]
  public function commentCreate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_create' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for file entities.
   */
  #[Hook('file_create')]
  public function fileCreate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_create' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for node entities.
   */
  #[Hook('node_create')]
  public function nodeCreate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_create' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_create')]
  public function taxonomyTermCreate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_create' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_create')]
  public function taxonomyVocabularyCreate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_create' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for user entities.
   */
  #[Hook('user_create')]
  public function userCreate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_create' . ' called';
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity) {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_presave' . ' called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for block entities.
   */
  #[Hook('block_presave')]
  public function blockPresave() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_presave' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for comment entities.
   */
  #[Hook('comment_presave')]
  public function commentPresave() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_presave' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for file entities.
   */
  #[Hook('file_presave')]
  public function filePresave() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_presave' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for node entities.
   */
  #[Hook('node_presave')]
  public function nodePresave() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_presave' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_presave')]
  public function taxonomyTermPresave() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_presave' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_presave')]
  public function taxonomyVocabularyPresave() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_presave' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for user entities.
   */
  #[Hook('user_presave')]
  public function userPresave() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_presave' . ' called';
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity) {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_insert' . ' called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for block entities.
   */
  #[Hook('block_insert')]
  public function blockInsert() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_insert' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for comment entities.
   */
  #[Hook('comment_insert')]
  public function commentInsert() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_insert' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for file entities.
   */
  #[Hook('file_insert')]
  public function fileInsert() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_insert' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for node entities.
   */
  #[Hook('node_insert')]
  public function nodeInsert() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_insert' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_insert')]
  public function taxonomyTermInsert() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_insert' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_insert')]
  public function taxonomyVocabularyInsert() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_insert' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for user entities.
   */
  #[Hook('user_insert')]
  public function userInsert() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_insert' . ' called';
  }

  /**
   * Implements hook_entity_preload().
   */
  #[Hook('entity_preload')]
  public function entityPreload(array $entities, $type) {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_preload' . ' called for type ' . $type;
  }

  /**
   * Implements hook_entity_load().
   */
  #[Hook('entity_load')]
  public function entityLoad(array $entities, $type) {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_load' . ' called for type ' . $type;
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for block entities.
   */
  #[Hook('block_load')]
  public function blockLoad() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_load' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for comment entities.
   */
  #[Hook('comment_load')]
  public function commentLoad() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_load' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for file entities.
   */
  #[Hook('file_load')]
  public function fileLoad() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_load' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for node entities.
   */
  #[Hook('node_load')]
  public function nodeLoad() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_load' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_load')]
  public function taxonomyTermLoad() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_load' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_load')]
  public function taxonomyVocabularyLoad() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_load' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_load() for user entities.
   */
  #[Hook('user_load')]
  public function userLoad() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_load' . ' called';
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity) {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_update' . ' called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for block entities.
   */
  #[Hook('block_update')]
  public function blockUpdate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_update' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for comment entities.
   */
  #[Hook('comment_update')]
  public function commentUpdate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_update' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for file entities.
   */
  #[Hook('file_update')]
  public function fileUpdate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_update' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for node entities.
   */
  #[Hook('node_update')]
  public function nodeUpdate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_update' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_update')]
  public function taxonomyTermUpdate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_update' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_update')]
  public function taxonomyVocabularyUpdate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_update' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for user entities.
   */
  #[Hook('user_update')]
  public function userUpdate() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_update' . ' called';
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity) {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_predelete' . ' called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for block entities.
   */
  #[Hook('block_predelete')]
  public function blockPredelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_predelete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for comment entities.
   */
  #[Hook('comment_predelete')]
  public function commentPredelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_predelete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for file entities.
   */
  #[Hook('file_predelete')]
  public function filePredelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_predelete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for node entities.
   */
  #[Hook('node_predelete')]
  public function nodePredelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_predelete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_predelete')]
  public function taxonomyTermPredelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_predelete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_predelete')]
  public function taxonomyVocabularyPredelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_predelete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for user entities.
   */
  #[Hook('user_predelete')]
  public function userPredelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_predelete' . ' called';
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity) {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_entity_delete' . ' called for type ' . $entity->getEntityTypeId();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for block entities.
   */
  #[Hook('block_delete')]
  public function blockDelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_block_delete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for comment entities.
   */
  #[Hook('comment_delete')]
  public function commentDelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_comment_delete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for file entities.
   */
  #[Hook('file_delete')]
  public function fileDelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_file_delete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for node entities.
   */
  #[Hook('node_delete')]
  public function nodeDelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_node_delete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for taxonomy_term entities.
   */
  #[Hook('taxonomy_term_delete')]
  public function taxonomyTermDelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_term_delete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for taxonomy_vocabulary entities.
   */
  #[Hook('taxonomy_vocabulary_delete')]
  public function taxonomyVocabularyDelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_taxonomy_vocabulary_delete' . ' called';
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for user entities.
   */
  #[Hook('user_delete')]
  public function userDelete() {
    $GLOBALS['entity_crud_hook_test'][] = 'entity_crud_hook_test_user_delete' . ' called';
  }

}
