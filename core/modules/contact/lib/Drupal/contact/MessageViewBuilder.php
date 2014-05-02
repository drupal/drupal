<?php

/**
 * @file
 * Contains Drupal\contact\MessageViewBuilder.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Component\Utility\String;
use Drupal\Core\Render\Element;

/**
 * Render controller for contact messages.
 */
class MessageViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildComponents($build, $entities, $displays, $view_mode, $langcode);

    foreach ($entities as $id => $entity) {
      // Add the message extra field, if enabled.
      $display = $displays[$entity->bundle()];
      if ($entity->getMessage() && $display->getComponent('message')) {
        $build[$id]['message'] = array(
          '#type' => 'item',
          '#title' => t('Message'),
          '#markup' => String::checkPlain($entity->getMessage()),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    if ($view_mode == 'mail') {
      // Convert field labels into headings.
      // @todo Improve drupal_html_to_text() to convert DIVs correctly.
      foreach (Element::children($build) as $key) {
        if (isset($build[$key]['#label_display']) && $build[$key]['#label_display'] == 'above') {
          $build[$key] += array('#prefix' => '');
          $build[$key]['#prefix'] = $build[$key]['#title'] . ":\n";
          $build[$key]['#label_display'] = 'hidden';
        }
      }
      $build = array(
        '#markup' => drupal_html_to_text(drupal_render($build)),
      );
    }
    return $build;
  }

}
