<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\row\CommentRow.
 */

namespace Drupal\comment\Plugin\views\row;

use Drupal\system\Plugin\views\row\EntityRow;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin which performs a comment_view on the resulting object.
 *
 * @Plugin(
 *   id = "comment",
 *   module = "comment",
 *   title = @Translation("Comment"),
 *   help = @Translation("Display the comment with standard comment view."),
 *   theme = "views_view_row_comment",
 *   base = {"comment"},
 *   entity_type = "comment",
 *   type = "normal"
 * )
 */
class CommentRow extends EntityRow {

  /**
   * Overrides Drupal\system\Plugin\views\row\Entity::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['links'] = array('default' => TRUE);
    $options['view_mode']['default'] = 'full';
    return $options;
  }

  /**
   * Overrides Drupal\system\Plugin\views\row\Entity::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['links'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display links'),
      '#default_value' => $this->options['links'],
    );
  }

  /**
   * Overrides Drupal\system\Plugin\views\row\Entity::render().
   */
  function render($row) {
    $entity_id = $row->{$this->field_alias};
    $build = $this->build[$entity_id];
    if (!$this->options['links']) {
      unset($build['links']);
    }
    return drupal_render($build);
  }

}
