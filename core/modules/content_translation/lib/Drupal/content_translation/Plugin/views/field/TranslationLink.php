<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\views\field\TranslationLink.
 */

namespace Drupal\content_translation\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a translation link for an entity.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("content_translation_link")
 */
class TranslationLink extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::render().
   */
  public function render($values) {
    return $this->render_link($this->getEntity($values), $values);
  }

  /**
   * Alters the field to render a link.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being rendered.
   * @param \stdClass $values
   *   The current row of the views result.
   *
   * @return string
   *   The acutal rendered text (without the link) of this field.
   */
  public function render_link(EntityInterface $entity, \stdClass $values) {
    if (content_translation_translate_access($entity)) {
      $text = !empty($this->options['text']) ? $this->options['text'] : t('translate');

      $this->options['alter']['make_link'] = TRUE;
      $uri = $entity->uri();
      $this->options['alter']['path'] = $uri['path'] . '/translations';

      return $text;
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::query().
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

}
