<?php

/**
 * @file
 * Contains \Drupal\node\NodePermissions.
 */

namespace Drupal\node;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Defines a class containing permission callbacks.
 */
class NodePermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Gets an array of node type permissions.
   *
   * @return array
   *   The node type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function nodeTypePermissions() {
    $perms = array();
    // Generate node permissions for all node types.
    foreach (NodeType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Builds a standard list of node permissions for a given type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The machine name of the node type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      "create $type_id content" => array(
        'title' => $this->t('%type_name: Create new content', $type_params),
      ),
      "edit own $type_id content" => array(
        'title' => $this->t('%type_name: Edit own content', $type_params),
      ),
      "edit any $type_id content" => array(
        'title' => $this->t('%type_name: Edit any content', $type_params),
      ),
      "delete own $type_id content" => array(
        'title' => $this->t('%type_name: Delete own content', $type_params),
      ),
      "delete any $type_id content" => array(
        'title' => $this->t('%type_name: Delete any content', $type_params),
      ),
      "view $type_id revisions" => array(
        'title' => $this->t('%type_name: View revisions', $type_params),
      ),
      "revert $type_id revisions" => array(
        'title' => $this->t('%type_name: Revert revisions', $type_params),
        'description' => t('Role requires permission <em>view revisions</em> and <em>edit rights</em> for nodes in question, or <em>administer nodes</em>.'),
      ),
      "delete $type_id revisions" => array(
        'title' => $this->t('%type_name: Delete revisions', $type_params),
        'description' => $this->t('Role requires permission to <em>view revisions</em> and <em>delete rights</em> for nodes in question, or <em>administer nodes</em>.'),
      ),
    );
  }

}
