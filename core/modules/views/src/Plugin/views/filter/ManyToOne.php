<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ManyToOneHelper;

/**
 * Complex filter to handle filtering for many to one relationships,
 * such as terms (many terms per node) or roles (many roles per user).
 *
 * The construct method needs to be overridden to provide a list of options;
 * alternately, the valueForm and adminSummary methods need to be overridden
 * to provide something that isn't just a select list.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("many_to_one")
 */
class ManyToOne extends InOperator {

  /**
   * @var \Drupal\views\ManyToOneHelper
   *
   * Stores the Helper object which handles the many_to_one complexity.
   */
  public $helper = NULL;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->helper = new ManyToOneHelper($this);
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 'or';
    $options['value']['default'] = [];

    if (isset($this->helper)) {
      $this->helper->defineOptions($options);
    }
    else {
      $helper = new ManyToOneHelper($this);
      $helper->defineOptions($options);
    }

    return $options;
  }

  public function operators() {
    $operators = [
      'or' => [
        'title' => $this->t('Is one of'),
        'short' => $this->t('or'),
        'short_single' => $this->t('='),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ],
      'and' => [
        'title' => $this->t('Is all of'),
        'short' => $this->t('and'),
        'short_single' => $this->t('='),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ],
      'not' => [
        'title' => $this->t('Is none of'),
        'short' => $this->t('not'),
        'short_single' => $this->t('<>'),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ],
    ];
    // if the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += [
        'empty' => [
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ],
        'not empty' => [
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ],
      ];
    }

    return $operators;
  }

  protected $valueFormType = 'select';
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    if (!$form_state->get('exposed')) {
      $this->helper->buildOptionsForm($form, $form_state);
    }
  }

  /**
   * Override ensureMyTable so we can control how this joins in.
   * The operator actually has influence over joining.
   */
  public function ensureMyTable() {
    // Defer to helper if the operator specifies it.
    $info = $this->operators();
    if (isset($info[$this->operator]['ensure_my_table']) && $info[$this->operator]['ensure_my_table'] == 'helper') {
      return $this->helper->ensureMyTable();
    }

    return parent::ensureMyTable();
  }

  protected function opHelper() {
    if (empty($this->value)) {
      return;
    }
    $this->helper->addFilter();
  }

}
