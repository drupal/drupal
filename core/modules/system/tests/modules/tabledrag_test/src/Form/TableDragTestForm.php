<?php

namespace Drupal\tabledrag_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for draggable table testing.
 */
class TableDragTestForm extends FormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a TableDragTestForm object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('state'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tabledrag_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        [
          'data' => $this->t('Text'),
          'colspan' => 4,
        ],
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'tabledrag-test-weight',
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'tabledrag-test-parent',
          'subgroup' => 'tabledrag-test-parent',
          'source' => 'tabledrag-test-id',
          'hidden' => TRUE,
          'limit' => 2,
        ],
        [
          'action' => 'depth',
          'relationship' => 'group',
          'group' => 'tabledrag-test-depth',
          'hidden' => TRUE,
        ],
      ],
      '#attributes' => ['id' => 'tabledrag-test-table'],
      '#attached' => ['library' => ['tabledrag_test/tabledrag']],
    ];

    // Provide a default set of five rows.
    $rows = $this->state->get('tabledrag_test_table', array_flip(range(1, 5)));
    foreach ($rows as $id => $row) {
      if (!is_array($row)) {
        $row = [];
      }

      $row += [
        'parent' => '',
        'weight' => 0,
        'depth' => 0,
        'classes' => [],
        'draggable' => TRUE,
      ];

      if (!empty($row['draggable'])) {
        $row['classes'][] = 'draggable';
      }

      $form['table'][$id] = [
        'title' => [
          'indentation' => [
            '#theme' => 'indentation',
            '#size' => $row['depth'],
          ],
          '#plain_text' => "Row with id $id",
        ],
        'id' => [
          '#type' => 'hidden',
          '#value' => $id,
          '#attributes' => ['class' => ['tabledrag-test-id']],
        ],
        'parent' => [
          '#type' => 'hidden',
          '#default_value' => $row['parent'],
          '#parents' => ['table', $id, 'parent'],
          '#attributes' => ['class' => ['tabledrag-test-parent']],
        ],
        'depth' => [
          '#type' => 'hidden',
          '#default_value' => $row['depth'],
          '#attributes' => ['class' => ['tabledrag-test-depth']],
        ],
        'weight' => [
          '#type' => 'weight',
          '#default_value' => $row['weight'],
          '#attributes' => ['class' => ['tabledrag-test-weight']],
        ],
        '#attributes' => ['class' => $row['classes']],
      ];
    }

    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $test_table = [];
    foreach ($form_state->getValue('table') as $row) {
      $test_table[$row['id']] = $row;
    }

    $this->state->set('tabledrag_test_table', $test_table);
  }

}
