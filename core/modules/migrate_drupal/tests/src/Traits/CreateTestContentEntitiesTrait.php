<?php

namespace Drupal\Tests\migrate_drupal\Traits;

/**
 * Provides helper methods for creating test content.
 */
trait CreateTestContentEntitiesTrait {

  /**
   * Gets required modules.
   *
   * @return array
   */
  protected function getRequiredModules() {
    return [
      'block_content',
      'comment',
      'field',
      'file',
      'link',
      'menu_link_content',
      'migrate_drupal',
      'node',
      'options',
      'system',
      'taxonomy',
      'text',
      'user',
    ];
  }

  /**
   * Install required entity schemas.
   */
  protected function installEntitySchemas() {
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
  }

  /**
   * Create several pieces of generic content.
   */
  protected function createContent() {
    $entity_type_manager = \Drupal::entityTypeManager();

    // Create a block content.
    if ($entity_type_manager->hasDefinition('block_content')) {
      $block = $entity_type_manager->getStorage('block_content')->create([
        'info' => 'block',
        'type' => 'block',
      ]);
      $block->save();
    }

    // Create a node.
    if ($entity_type_manager->hasDefinition('node')) {
      $node = $entity_type_manager->getStorage('node')->create([
        'type' => 'page',
        'title' => 'page',
      ]);
      $node->save();

      // Create a comment.
      if ($entity_type_manager->hasDefinition('comment')) {
        $comment = $entity_type_manager->getStorage('comment')->create([
          'comment_type' => 'comment',
          'field_name' => 'comment',
          'entity_type' => 'node',
          'entity_id' => $node->id(),
        ]);
        $comment->save();
      }
    }

    // Create a file.
    if ($entity_type_manager->hasDefinition('file')) {
      $file = $entity_type_manager->getStorage('file')->create([
        'uri' => 'public://example.txt',
      ]);
      $file->save();
    }

    // Create a menu link.
    if ($entity_type_manager->hasDefinition('menu_link_content')) {
      $menu_link = $entity_type_manager->getStorage('menu_link_content')->create([
        'title' => 'menu link',
        'link' => ['uri' => 'http://www.example.com'],
        'menu_name' => 'tools',
      ]);
      $menu_link->save();
    }

    // Create a taxonomy term.
    if ($entity_type_manager->hasDefinition('taxonomy_term')) {
      $term = $entity_type_manager->getStorage('taxonomy_term')->create([
        'name' => 'term',
        'vid' => 'term',
      ]);
      $term->save();
    }

    // Create a user.
    if ($entity_type_manager->hasDefinition('user')) {
      $user = $entity_type_manager->getStorage('user')->create([
        'name' => 'user',
        'mail' => 'user@example.com',
      ]);
      $user->save();
    }
  }

  /**
   * Create several pieces of generic content.
   */
  protected function createContentPostUpgrade() {
    $entity_type_manager = \Drupal::entityTypeManager();

    // Create a block content.
    if ($entity_type_manager->hasDefinition('block_content')) {
      $block = $entity_type_manager->getStorage('block_content')->create([
        'info' => 'Post upgrade block',
        'type' => 'block',
      ]);
      $block->save();
    }

    // Create a node.
    if ($entity_type_manager->hasDefinition('node')) {
      $node = $entity_type_manager->getStorage('node')->create([
        'type' => 'page',
        'title' => 'Post upgrade page',
      ]);
      $node->save();

      // Create a comment.
      if ($entity_type_manager->hasDefinition('comment')) {
        $comment = $entity_type_manager->getStorage('comment')->create([
          'comment_type' => 'comment',
          'field_name' => 'comment',
          'entity_type' => 'node',
          'entity_id' => $node->id(),
        ]);
        $comment->save();
      }
    }

    // Create a file.
    if ($entity_type_manager->hasDefinition('file')) {
      $file = $entity_type_manager->getStorage('file')->create([
        'uri' => 'public://post_upgrade_example.txt',
      ]);
      $file->save();
    }

    // Create a menu link.
    if ($entity_type_manager->hasDefinition('menu_link_content')) {
      $menu_link = $entity_type_manager->getStorage('menu_link_content')->create([
        'title' => 'post upgrade menu link',
        'link' => ['uri' => 'http://www.drupal.org'],
        'menu_name' => 'tools',
      ]);
      $menu_link->save();
    }

    // Create a taxonomy term.
    if ($entity_type_manager->hasDefinition('taxonomy_term')) {
      $term = $entity_type_manager->getStorage('taxonomy_term')->create([
        'name' => 'post upgrade term',
        'vid' => 'term',
      ]);
      $term->save();
    }

    // Create a user.
    if ($entity_type_manager->hasDefinition('user')) {
      $user = $entity_type_manager->getStorage('user')->create([
        'name' => 'universe',
        'mail' => 'universe@example.com',
      ]);
      $user->save();
    }
  }

}
