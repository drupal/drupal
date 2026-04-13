<?php

declare(strict_types=1);

namespace Drupal\menu_ui;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Utility functions for menu_ui.
 */
class MenuUiUtility {

  public function __construct(
    protected EntityRepositoryInterface $entityRepository,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * Helper function to create or update a menu link for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   * @param array $values
   *   Values for the menu link.
   *
   * @internal
   */
  public function menuUiNodeSave(NodeInterface $node, array $values): void {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
    if (!empty($values['entity_id'])) {
      $entity = $this->entityRepository->getActive('menu_link_content', $values['entity_id']);
      if ($entity->isTranslatable() && $node->isTranslatable()) {
        if (!$entity->hasTranslation($node->language()->getId())) {
          $entity = $entity->addTranslation($node->language()->getId(), $entity->toArray());
        }
        else {
          $entity = $entity->getTranslation($node->language()->getId());
        }
      }
      else {
        // Ensure the entity matches the node language.
        $entity = $entity->getUntranslated();
        $entity->set($entity->getEntityType()->getKey('langcode'), $node->language()->getId());
      }
    }
    else {
      // Create a new menu_link_content entity.
      $entity = $this->entityTypeManager->getStorage('menu_link_content')->create([
        'link' => ['uri' => 'entity:node/' . $node->id()],
        'langcode' => $node->language()->getId(),
      ]);
      $entity->setPublished();
    }
    $entity->title->value = trim($values['title']);
    $entity->description->value = trim($values['description']);
    $entity->menu_name->value = $values['menu_name'];
    $entity->parent->value = $values['parent'];
    $entity->weight->value = $values['weight'] ?? 0;
    if ($entity->isNew()) {
      // @todo The menu link doesn't need to be changed in a workspace context.
      //   Fix this in https://www.drupal.org/project/drupal/issues/3511204.
      if (!$node->isDefaultRevision() && $node->hasLinkTemplate('latest-version')) {
        // If a new menu link is created while saving the node as a pending
        // draft (non-default revision), store it as a link to the latest
        // version. That ensures that there is a regular, valid link target
        // that is only visible to users with permission to view the latest
        // version.
        $entity->get('link')->uri = 'internal:/node/' . $node->id() . '/latest';
      }
    }
    else {
      $entity->isDefaultRevision($node->isDefaultRevision());
      if (!$entity->isDefaultRevision()) {
        $entity->setNewRevision();
      }
      elseif ($entity->get('link')->uri !== 'entity:node/' . $node->id()) {
        $entity->get('link')->uri = 'entity:node/' . $node->id();
      }
    }
    $entity->save();
  }

  /**
   * Returns the definition for a menu link for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return array|bool
   *   An array that contains default values for the menu link form. FALSE as
   *   a fallback.
   */
  public function getMenuLinkDefaults(NodeInterface $node): false|array {
    // Prepare the definition for the edit form.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $node->type->entity;
    $menu_name = strtok($node_type->getThirdPartySetting('menu_ui', 'parent', 'main:'), ':');
    $defaults = FALSE;
    if ($node->id()) {
      $id = FALSE;
      // Give priority to the default menu.
      $type_menus = $node_type->getThirdPartySetting('menu_ui', 'available_menus', ['main']);
      // An existing menu link either points to the canonical or the latest
      // path, in case of a new menu link that was creating while saving as a
      // pending draft.
      $uri_candidates = ['entity:node/' . $node->id(), 'internal:/node/' . $node->id() . '/latest'];

      if (in_array($menu_name, $type_menus)) {
        $query = $this->entityTypeManager->getStorage('menu_link_content')
          ->getQuery()
          ->accessCheck()
          ->condition('link.uri', $uri_candidates, 'IN')
          ->condition('menu_name', $menu_name)
          ->sort('id')
          ->range(0, 1);
        $result = $query->execute();

        $id = (!empty($result)) ? reset($result) : FALSE;
      }
      // Check all allowed menus if a link does not exist in the default menu.
      if (!$id && !empty($type_menus)) {
        $query = $this->entityTypeManager->getStorage('menu_link_content')
          ->getQuery()
          ->accessCheck()
          ->condition('link.uri', $uri_candidates, 'IN')
          ->condition('menu_name', array_values($type_menus), 'IN')
          ->sort('id')
          ->range(0, 1);
        $result = $query->execute();

        $id = (!empty($result)) ? reset($result) : FALSE;
      }
      if ($id) {
        $menu_link = $this->entityRepository->getActive('menu_link_content', $id);
        $defaults = [
          'entity_id' => $menu_link->id(),
          'id' => $menu_link->getPluginId(),
          'title' => $menu_link->getTitle(),
          'title_max_length' => $menu_link->getFieldDefinitions()['title']->getSetting('max_length'),
          'description' => $menu_link->getDescription(),
          'description_max_length' => $menu_link->getFieldDefinitions()['description']->getSetting('max_length'),
          'menu_name' => $menu_link->getMenuName(),
          'parent' => $menu_link->getParentId(),
          'weight' => $menu_link->getWeight(),
        ];
      }
    }

    if (!$defaults) {
      // Get the default max_length of a menu link title from the base field
      // definition.
      $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions('menu_link_content');
      $max_length = $field_definitions['title']->getSetting('max_length');
      $description_max_length = $field_definitions['description']->getSetting('max_length');
      $defaults = [
        'entity_id' => 0,
        'id' => '',
        'title' => '',
        'title_max_length' => $max_length,
        'description' => '',
        'description_max_length' => $description_max_length,
        'menu_name' => $menu_name,
        'parent' => '',
        'weight' => 0,
      ];
    }
    return $defaults;
  }

}
