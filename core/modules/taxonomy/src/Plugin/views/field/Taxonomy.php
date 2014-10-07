<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\field\Taxonomy.
 */

namespace Drupal\taxonomy\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide simple renderer that allows linking to a taxonomy
 * term.
 *
 * @todo This handler should use entities directly.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("taxonomy")
 */
class Taxonomy extends FieldPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   *
   * This method assumes the taxonomy_term_data table. If using another table,
   * we'll need to be more specific.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['vid'] = 'vid';
    $this->additional_fields['tid'] = 'tid';
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_taxonomy'] = array('default' => FALSE);
    $options['convert_spaces'] = array('default' => FALSE);
    return $options;
  }

  /**
   * Provide link to taxonomy option
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_taxonomy'] = array(
      '#title' => $this->t('Link this field to its taxonomy term page'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_taxonomy']),
    );
     $form['convert_spaces'] = array(
      '#title' => $this->t('Convert spaces in term names to hyphens'),
      '#description' => $this->t('This allows links to work with Views taxonomy term arguments.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['convert_spaces']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares a link to the taxonomy.
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
    $term = $this->getEntity($values);

    if (!empty($this->options['link_to_taxonomy']) && $term && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $term->getSystemPath();
    }

    if (!empty($this->options['convert_spaces'])) {
      $data = str_replace(' ', '-', $data);
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
