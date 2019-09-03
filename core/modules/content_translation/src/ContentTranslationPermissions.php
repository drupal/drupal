<?php

namespace Drupal\content_translation;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for the content_translation module.
 */
class ContentTranslationPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a ContentTranslationPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentTranslationManagerInterface $content_translation_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->contentTranslationManager = $content_translation_manager;
    if (!$entity_type_bundle_info) {
      @trigger_error('Calling ContentTranslationPermissions::__construct() with the $entity_type_bundle_info argument is supported in drupal:8.7.0 and will be required before drupal:9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
    }
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('content_translation.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Returns an array of content translation permissions.
   *
   * @return array
   */
  public function contentPermissions() {
    $permission = [];
    // Create a translate permission for each enabled entity type and (optionally)
    // bundle.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($permission_granularity = $entity_type->getPermissionGranularity()) {
        $t_args = ['@entity_label' => $entity_type->getSingularLabel()];

        switch ($permission_granularity) {
          case 'bundle':
            foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type_id) as $bundle => $bundle_info) {
              if ($this->contentTranslationManager->isEnabled($entity_type_id, $bundle)) {
                $t_args['%bundle_label'] = isset($bundle_info['label']) ? $bundle_info['label'] : $bundle;
                $permission["translate $bundle $entity_type_id"] = [
                  'title' => $this->t('Translate %bundle_label @entity_label', $t_args),
                ];
              }
            }
            break;

          case 'entity_type':
            if ($this->contentTranslationManager->isEnabled($entity_type_id)) {
              $permission["translate $entity_type_id"] = [
                'title' => $this->t('Translate @entity_label', $t_args),
              ];
            }
            break;
        }
      }
    }

    return $permission;
  }

}
