<?php

/**
 * @file
 * Contains \Drupal\content_translation\ContentTranslationPermissions.
 */

namespace Drupal\content_translation;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for the content_translation module.
 */
class ContentTranslationPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ContentTranslationPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
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
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($permission_granularity = $entity_type->getPermissionGranularity()) {
        $t_args = ['@entity_label' => $entity_type->getLowercaseLabel()];

        switch ($permission_granularity) {
          case 'bundle':
            foreach ($this->entityManager->getBundleInfo($entity_type_id) as $bundle => $bundle_info) {
              if (content_translation_enabled($entity_type_id, $bundle)) {
                $t_args['%bundle_label'] = isset($bundle_info['label']) ? $bundle_info['label'] : $bundle;
                $permission["translate $bundle $entity_type_id"] = [
                  'title' => $this->t('Translate %bundle_label @entity_label', $t_args),
                ];
              }
            }
            break;

          case 'entity_type':
            if (content_translation_enabled($entity_type_id)) {
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
