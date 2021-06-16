<?php

namespace Drupal\layout_builder;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for Layout Builder overrides.
 *
 * @see \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage::access()
 *
 * @internal
 *   Dynamic permission callbacks are internal.
 */
class LayoutBuilderOverridesPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * LayoutBuilderOverridesPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle info service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Returns an array of permissions.
   *
   * @return string[][]
   *   An array whose keys are permission names and whose corresponding values
   *   are defined in \Drupal\user\PermissionHandlerInterface::getPermissions().
   */
  public function permissions() {
    $permissions = [];

    /** @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface[] $entity_displays */
    $entity_displays = $this->entityTypeManager->getStorage('entity_view_display')->loadByProperties(['third_party_settings.layout_builder.allow_custom' => TRUE]);
    foreach ($entity_displays as $entity_display) {
      $entity_type_id = $entity_display->getTargetEntityTypeId();
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $bundle = $entity_display->getTargetBundle();
      $args = [
        '%entity_type' => $entity_type->getCollectionLabel(),
        '@entity_type_singular' => $entity_type->getSingularLabel(),
        '@entity_type_plural' => $entity_type->getPluralLabel(),
        '%bundle' => $this->bundleInfo->getBundleInfo($entity_type_id)[$bundle]['label'],
      ];
      if ($entity_type->hasKey('bundle')) {
        $permissions["configure all $bundle $entity_type_id layout overrides"] = [
          'title' => $this->t('%entity_type - %bundle: Configure all layout overrides', $args),
          'warning' => $this->t('Warning: Allows configuring the layout even if the user cannot edit the @entity_type_singular itself.', $args),
        ];
        $permissions["configure editable $bundle $entity_type_id layout overrides"] = [
          'title' => $this->t('%entity_type - %bundle: Configure layout overrides for @entity_type_plural that the user can edit', $args),
        ];
      }
      else {
        $permissions["configure all $bundle $entity_type_id layout overrides"] = [
          'title' => $this->t('%entity_type: Configure all layout overrides', $args),
          'warning' => $this->t('Warning: Allows configuring the layout even if the user cannot edit the @entity_type_singular itself.', $args),
        ];
        $permissions["configure editable $bundle $entity_type_id layout overrides"] = [
          'title' => $this->t('%entity_type: Configure layout overrides for @entity_type_plural that the user can edit', $args),
        ];
      }
    }
    return $permissions;
  }

}
