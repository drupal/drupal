<?php

namespace Drupal\node;

use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class NodePermissions {
  use BundlePermissionHandlerTrait;
  use StringTranslationTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The node type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function nodeTypePermissions() {
    return $this->generatePermissions(NodeType::loadMultiple(), [$this, 'buildPermissions']);
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id content" => [
        'title' => $this->t('%type_name: Create new content', $type_params),
      ],
      "edit own $type_id content" => [
        'title' => $this->t('%type_name: Edit own content', $type_params),
      ],
      "edit any $type_id content" => [
        'title' => $this->t('%type_name: Edit any content', $type_params),
      ],
      "delete own $type_id content" => [
        'title' => $this->t('%type_name: Delete own content', $type_params),
      ],
      "delete any $type_id content" => [
        'title' => $this->t('%type_name: Delete any content', $type_params),
      ],
      "view $type_id revisions" => [
        'title' => $this->t('%type_name: View revisions', $type_params),
        'description' => t('To view a revision, you also need permission to view the content item.'),
      ],
      "revert $type_id revisions" => [
        'title' => $this->t('%type_name: Revert revisions', $type_params),
        'description' => t('To revert a revision, you also need permission to edit the content item.'),
      ],
      "delete $type_id revisions" => [
        'title' => $this->t('%type_name: Delete revisions', $type_params),
        'description' => $this->t('To delete a revision, you also need permission to delete the content item.'),
      ],
    ];
  }

}
