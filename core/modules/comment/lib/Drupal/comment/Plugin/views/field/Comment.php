<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\Comment.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to allow linking to a comment.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment")
 */
class Comment extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::init().
   *
   * Provide generic option to link to comment.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['link_to_comment'])) {
      $this->additional_fields['cid'] = 'cid';
      $this->additional_fields['nid'] = 'nid';
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_comment'] = array('default' => TRUE, 'bool' => TRUE);
    $options['link_to_node'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide link-to-comment option
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_comment'] = array(
      '#title' => t('Link this field to its comment'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_comment'],
    );
    $form['link_to_node'] = array(
      '#title' => t('Link field to the node if there is no comment.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_node'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_comment'])) {
      $this->options['alter']['make_link'] = TRUE;
      $nid = $this->getValue($values, 'nid');
      $cid = $this->getValue($values, 'cid');
      if (!empty($cid)) {
        $this->options['alter']['path'] = "comment/" . $cid;
        $this->options['alter']['fragment'] = "comment-" . $cid;
      }
      // If there is no comment link to the node.
      elseif ($this->options['link_to_node']) {
        $this->options['alter']['path'] = "node/" . $nid;
      }
    }

    return $data;
  }

  public function render($values) {
    $value = $this->getValue($values);
    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
