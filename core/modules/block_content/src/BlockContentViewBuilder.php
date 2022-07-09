<?php

namespace Drupal\block_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for custom blocks.
 *
 * Note: Custom blocks (block_content entities) are not designed to be displayed
 * outside of blocks! This BlockContentViewBuilder class is designed to be used
 * by \Drupal\block_content\Plugin\Block\BlockContentBlock::build() and by
 * nothing else.
 *
 * @see \Drupal\block_content\Plugin\Block\BlockContentBlock
 */
class BlockContentViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    return $this->viewMultiple([$entity], $view_mode, $langcode)[0];
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build_list = parent::viewMultiple($entities, $view_mode, $langcode);
    // Apply the buildMultiple() #pre_render callback immediately, to make
    // bubbling of attributes and contextual links to the actual block work.
    // @see \Drupal\block\BlockViewBuilder::buildBlock()
    unset($build_list['#pre_render'][0]);
    return $this->buildMultiple($build_list);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The custom block will be rendered in the wrapped block template already
    // and thus has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
