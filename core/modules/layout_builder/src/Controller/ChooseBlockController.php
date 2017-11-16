<?php

namespace Drupal\layout_builder\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to choose a new block.
 *
 * @internal
 */
class ChooseBlockController implements ContainerInjectionInterface {

  use AjaxHelperTrait;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * ChooseBlockController constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(BlockManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Provides the UI for choosing a new block.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param int $delta
   *   The delta of the section to splice.
   * @param string $region
   *   The region the block is going in.
   *
   * @return array
   *   A render array.
   */
  public function build(EntityInterface $entity, $delta, $region) {
    $build['#type'] = 'container';
    $build['#attributes']['class'][] = 'block-categories';

    foreach ($this->blockManager->getGroupedDefinitions() as $category => $blocks) {
      $build[$category]['#type'] = 'details';
      $build[$category]['#open'] = TRUE;
      $build[$category]['#title'] = $category;
      $build[$category]['links'] = [
        '#theme' => 'links',
      ];
      foreach ($blocks as $block_id => $block) {
        $link = [
          'title' => $block['admin_label'],
          'url' => Url::fromRoute('layout_builder.add_block',
            [
              'entity_type_id' => $entity->getEntityTypeId(),
              'entity' => $entity->id(),
              'delta' => $delta,
              'region' => $region,
              'plugin_id' => $block_id,
            ]
          ),
        ];
        if ($this->isAjax()) {
          $link['attributes']['class'][] = 'use-ajax';
          $link['attributes']['data-dialog-type'][] = 'dialog';
          $link['attributes']['data-dialog-renderer'][] = 'off_canvas';
        }
        $build[$category]['links']['#links'][] = $link;
      }
    }
    return $build;
  }

}
