<?php

/**
 * @file
 * Contains \Drupal\filter\FilterPermissions.
 */

namespace Drupal\filter;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the filter module.
 */
class FilterPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new FilterPermissions instance.
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
   * Returns an array of filter permissions.
   *
   * @return array
   */
  public function permissions() {
    $permissions = [];
    // Generate permissions for each text format. Warn the administrator that any
    // of them are potentially unsafe.
    /** @var \Drupal\filter\FilterFormatInterface[] $formats */
    $formats = $this->entityManager->getStorage('filter_format')->loadByProperties(['status' => TRUE]);
    uasort($formats, 'Drupal\Core\Config\Entity\ConfigEntityBase::sort');
    foreach ($formats as $format) {
      if ($permission = $format->getPermissionName()) {
        $permissions[$permission] = [
          'title' => $this->t('Use the <a href="@url">@label</a> text format', ['@url' => $format->url(), '@label' => $format->label()]),
          'description' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Warning: This permission may have security implications depending on how the text format is configured.'),
            '#suffix' => '</em>'
          ],
        ];
      }
    }
    return $permissions;
  }

}
