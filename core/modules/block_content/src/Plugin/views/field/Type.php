<?php

/**
 * @file
 * Contains \Drupal\block_content\Plugin\views\field\Type.
 */

namespace Drupal\block_content\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to translate a block content type into its readable form.
 *
 * @todo Remove this when https://www.drupal.org/node/2363811 is fixed.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("block_content_type")
 */
class Type extends BlockContent {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['machine_name'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['machine_name'] = array(
      '#title' => $this->t('Output machine name'),
      '#description' => $this->t('Display field as the block content type machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    );
  }

  /**
   * Renders block content type name.
   *
   * @param string $data
   *   The block content type machine_name.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   The block content type as human readable name or machine_name.
   */
  public function renderName($data, ResultRow $values) {
    if ($this->options['machine_name'] != 1 && $data !== NULL && $data !== '') {
      $type = $this->entityManager->getStorage('block_content_type')->load($data);
      return $type ? $this->t($this->sanitizeValue($type->label())) : '';
    }
    return $this->sanitizeValue($data);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->renderName($value, $values), $values);
  }

}
