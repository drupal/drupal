<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\views\field\File.
 */

namespace Drupal\file\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to provide simple renderer that allows linking to a file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file")
 */
class File extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($options['link_to_file'])) {
      $this->additional_fields['uri'] = 'uri';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_file'] = array('default' => FALSE);
    return $options;
  }

  /**
   * Provide link to file option
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_file'] = array(
      '#title' => $this->t('Link this field to download the file'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_file']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the file.
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
    if (!empty($this->options['link_to_file']) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      // @todo Wrap in file_url_transform_relative(). This is currently
      // impossible. As a work-around, we could add the 'url.site' cache context
      // to ensure different file URLs are generated for different sites in a
      // multisite setup, including HTTP and HTTPS versions of the same site.
      // But unfortunately it's impossible to bubble a cache context here.
      // Fix in https://www.drupal.org/node/2646744.
      $this->options['alter']['path'] = file_create_url($this->getValue($values, 'uri'));
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
