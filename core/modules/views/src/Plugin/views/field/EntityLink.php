<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_link")
 */
class EntityLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    return $this->getEntity($row) ? parent::render($row) : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    if ($this->options['output_url_as_text']) {
      $url_info = $this->getUrlInfo($row);
      return $url_info ? $url_info->toString() : '';
    }
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $template = $this->getEntityLinkTemplate();
    $entity = $this->getEntity($row);
    if ($entity === NULL) {
      return NULL;
    }
    if ($this->languageManager->isMultilingual()) {
      $entity = $this->getEntityTranslationByRelationship($entity, $row);
    }
    return $entity->toUrl($template)->setAbsolute($this->options['absolute']);
  }

  /**
   * Returns the entity link template name identifying the link route.
   *
   * @return string
   *   The link template name.
   */
  protected function getEntityLinkTemplate() {
    return 'canonical';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('view');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['output_url_as_text'] = ['default' => FALSE];
    $options['absolute'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['output_url_as_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Output the URL as text'),
      '#default_value' => $this->options['output_url_as_text'],
    ];
    $form['absolute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use absolute link (begins with "http://")'),
      '#default_value' => $this->options['absolute'],
      '#description' => $this->t('Enable this option to output an absolute link. Required if you want to use the path as a link destination.'),
    ];
    parent::buildOptionsForm($form, $form_state);
    // Only show the 'text' field if we don't want to output the raw URL.
    $form['text']['#states']['visible'][':input[name="options[output_url_as_text]"]'] = ['checked' => FALSE];
  }

}
