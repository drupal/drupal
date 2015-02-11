<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\Comment.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to allow linking to a comment.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("comment")
 */
class Comment extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::init().
   *
   * Provide generic option to link to comment.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['link_to_comment'])) {
      $this->additional_fields['cid'] = 'cid';
      $this->additional_fields['entity_id'] = array(
        'table' => 'comment_field_data',
        'field' => 'entity_id'
      );
      $this->additional_fields['entity_type'] = array(
        'table' => 'comment_field_data',
        'field' => 'entity_type'
      );
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_comment'] = array('default' => TRUE);
    $options['link_to_entity'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Provide link-to-comment option
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_comment'] = array(
      '#title' => $this->t('Link this field to its comment'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_comment'],
    );
    $form['link_to_entity'] = array(
      '#title' => $this->t('Link field to the entity if there is no comment'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_entity'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Render whatever the data is as a link to the comment or its node.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_comment'])) {
      $this->options['alter']['make_link'] = TRUE;
      $cid = $this->getValue($values, 'cid');
      if (!empty($cid)) {
        $this->options['alter']['url'] = Url::fromRoute('entity.comment.canonical', ['comment' => $cid]);
        $this->options['alter']['fragment'] = "comment-" . $cid;
      }
      // If there is no comment link to the entity.
      elseif ($this->options['link_to_entity']) {
        $entity_id = $this->getValue($values, 'entity_id');
        $entity_type = $this->getValue($values, 'entity_type');
        $entity = entity_load($entity_type, $entity_id);
        $this->options['alter']['url'] = $entity->urlInfo();
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
