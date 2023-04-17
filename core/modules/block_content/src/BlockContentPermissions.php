<?php

namespace Drupal\block_content;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide dynamic permissions for blocks of different types.
 */
class BlockContentPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use BundlePermissionHandlerTrait;

  /**
   * Constructs a BlockContentPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Build permissions for each block type.
   *
   * @return array
   *   The block type permissions.
   */
  public function blockTypePermissions() {
    return $this->generatePermissions($this->entityTypeManager->getStorage('block_content_type')->loadMultiple(), [$this, 'buildPermissions']);
  }

  /**
   * Return all the permissions available for a block type.
   *
   * @param \Drupal\block_content\Entity\BlockContentType $type
   *   The block type.
   *
   * @return array
   *   Permissions available for the given block type.
   */
  protected function buildPermissions(BlockContentType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];
    return [
      "create $type_id block content" => [
        'title' => $this->t('%type_name: Create new content block', $type_params),
      ],
      "edit any $type_id block content" => [
        'title' => $this->t('%type_name: Edit content block', $type_params),
      ],
      "delete any $type_id block content" => [
        'title' => $this->t('%type_name: Delete content block', $type_params),
      ],
      "view any $type_id block content history" => [
        'title' => $this->t('%type_name: View content block history pages', $type_params),
      ],
      "revert any $type_id block content revisions" => [
        'title' => $this->t('%type_name: Revert content block revisions', $type_params),
      ],
      "delete any $type_id block content revisions" => [
        'title' => $this->t('%type_name: Delete content block revisions', $type_params),
      ],
    ];
  }

}
