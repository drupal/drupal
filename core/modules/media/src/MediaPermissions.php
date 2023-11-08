<?php

namespace Drupal\media;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for each media type.
 */
class MediaPermissions implements ContainerInjectionInterface {
  use BundlePermissionHandlerTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MediaPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of media type permissions.
   *
   * @return array
   *   The media type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function mediaTypePermissions() {
    // Generate media permissions for all media types.
    $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
    return $this->generatePermissions($media_types, [$this, 'buildPermissions']);
  }

  /**
   * Returns a list of media permissions for a given media type.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(MediaTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id media" => [
        'title' => $this->t('%type_name: Create new media', $type_params),
      ],
      "edit own $type_id media" => [
        'title' => $this->t('%type_name: Edit own media', $type_params),
      ],
      "edit any $type_id media" => [
        'title' => $this->t('%type_name: Edit any media', $type_params),
      ],
      "delete own $type_id media" => [
        'title' => $this->t('%type_name: Delete own media', $type_params),
      ],
      "delete any $type_id media" => [
        'title' => $this->t('%type_name: Delete any media', $type_params),
      ],
      "view any $type_id media revisions" => [
        'title' => $this->t('%type_name: View any media revision pages', $type_params),
      ],
      "revert any $type_id media revisions" => [
        'title' => $this->t('Revert %type_name: Revert media revisions', $type_params),
      ],
      "delete any $type_id media revisions" => [
        'title' => $this->t('Delete %type_name: Delete media revisions', $type_params),
      ],
    ];
  }

}
