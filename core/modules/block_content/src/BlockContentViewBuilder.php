<?php

/**
 * @file
 * Contains \Drupal\block_content\BlockContentViewBuilder.
 */

namespace Drupal\block_content;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for custom blocks.
 */
class BlockContentViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    return $this->viewMultiple(array($entity), $view_mode, $langcode)[0];
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
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

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    parent::alterBuild($build, $entity, $display, $view_mode);
    // Add contextual links for this custom block.
    if (!$entity->isNew()) {
      $build['#contextual_links']['block_content'] = array(
        'route_parameters' => array('block_content' => $entity->id()),
        'metadata' => array('changed' => $entity->getChangedTime()),
      );
    }
  }

}
