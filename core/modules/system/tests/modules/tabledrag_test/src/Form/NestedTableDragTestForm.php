<?php

declare(strict_types=1);

namespace Drupal\tabledrag_test\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for testing nested draggable tables.
 */
class NestedTableDragTestForm extends TableDragTestForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nested_tabledrag_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parent_row_ids = ['parent_1', 'parent_2', 'parent_3'];
    $parent_rows = array_combine($parent_row_ids, $parent_row_ids);

    $form['table'] = $this->buildTestTable($parent_rows, 'tabledrag-test-parent-table', 'tabledrag-test-nested-parent', FALSE);

    $form['table']['#caption'] = $this->t('Parent table');
    $form['table'][reset($parent_row_ids)]['title'] = $this->buildTestTable() + ['#caption' => $this->t('Nested table')];

    $form['actions'] = $this->buildFormActions();

    return $form;
  }

}
