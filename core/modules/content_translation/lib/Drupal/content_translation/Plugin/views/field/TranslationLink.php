<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\views\field\TranslationLink.
 */

namespace Drupal\content_translation\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\ResultRow;

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
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->renderLink($this->getEntity($values), $values);
  }

  /**
   * Alters the field to render a link.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being rendered.
   * @param \Drupal\views\ResultRow $values
   *   The current row of the views result.
   *
   * @return string
   *   The actual rendered text (without the link) of this field.
   */
  protected function renderLink(EntityInterface $entity, ResultRow $values) {
    if (content_translation_translate_access($entity)) {
      $text = !empty($this->options['text']) ? $this->options['text'] : t('translate');

      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $entity->getSystemPath('drupal:content-translation-overview');

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
