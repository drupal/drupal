<?php

/**
 * @file
 * Definition of Drupal\file\Plugin\views\field\FileMime.
 */

namespace Drupal\file\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to add rendering MIME type images as an option on the filemime field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_filemime")
 */
class FileMime extends File {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['filemime_image'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['filemime_image'] = array(
      '#title' => $this->t('Display an icon representing the file type, instead of the MIME text (such as "image/jpeg")'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['filemime_image']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $data = $values->{$this->field_alias};
    if (!empty($this->options['filemime_image']) && $data !== NULL && $data !== '') {
      $file_icon = array(
        '#theme' => 'image__file_icon',
        '#file' => $values->_entity,
      );
      $data = drupal_render($file_icon);
    }

    return $this->renderLink($data, $values);
  }

}
