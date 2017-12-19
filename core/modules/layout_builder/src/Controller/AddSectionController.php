<?php

namespace Drupal\layout_builder\Controller;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Section;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a controller to add a new section.
 *
 * @internal
 */
class AddSectionController implements ContainerInjectionInterface {

  use AjaxHelperTrait;
  use LayoutRebuildTrait;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * AddSectionController constructor.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, ClassResolverInterface $class_resolver) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('class_resolver')
    );
  }

  /**
   * Add the layout to the entity field in a tempstore.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param int $delta
   *   The delta of the section to splice.
   * @param string $plugin_id
   *   The plugin ID of the layout to add.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The controller response.
   */
  public function build(EntityInterface $entity, $delta, $plugin_id) {
    /** @var \Drupal\layout_builder\SectionStorageInterface $field_list */
    $field_list = $entity->layout_builder__layout;
    $field_list->insertSection($delta, new Section($plugin_id));

    $this->layoutTempstoreRepository->set($entity);

    if ($this->isAjax()) {
      return $this->rebuildAndClose($entity);
    }
    else {
      $url = $entity->toUrl('layout-builder');
      return new RedirectResponse($url->setAbsolute()->toString());
    }
  }

}
