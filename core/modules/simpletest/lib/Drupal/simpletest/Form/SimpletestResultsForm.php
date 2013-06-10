<?php

/**
 * @file
 * Contains \Drupal\simpletest\Form\SimpletestResultsForm.
 */

namespace Drupal\simpletest\Form;

use Drupal\Core\ControllerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test results form for $test_id.
 */
class SimpletestResultsForm implements FormInterface, ControllerInterface {

  /**
   * Associative array of themed result images keyed by status.
   *
   * @var array
   */
  protected $statusImageMap;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Constructs a \Drupal\simpletest\Form\SimpletestResultsForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
    // Initialize image mapping property.
    $this->statusImageMap = array(
      'pass' => theme('image', array(
        'uri' => 'core/misc/watchdog-ok.png',
        'width' => 18,
        'height' => 18,
        'alt' => t('Pass')
      )),
      'fail' => theme('image', array(
        'uri' => 'core/misc/watchdog-error.png',
        'width' => 18,
        'height' => 18,
        'alt' => t('Fail')
      )),
      'exception' => theme('image', array(
        'uri' => 'core/misc/watchdog-warning.png',
        'width' => 18,
        'height' => 18,
        'alt' => t('Exception')
      )),
      'debug' => theme('image', array(
        'uri' => 'core/misc/watchdog-warning.png',
        'width' => 18,
        'height' => 18,
        'alt' => t('Debug')
      )),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'simpletest_results_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $test_id = NULL) {
    // Make sure there are test results to display and a re-run is not being performed.
    $results = array();

    if (is_numeric($test_id) && !$results = $this->getResults($test_id)) {
      drupal_set_message(t('No test results to display.'), 'error');
      drupal_goto('admin/config/development/testing');

      return $form;
    }

    // Load all classes and include CSS.
    $form['#attached']['css'][] = drupal_get_path('module', 'simpletest') . '/css/simpletest.module.css';

    // Keep track of which test cases passed or failed.
    $filter = array(
      'pass' => array(),
      'fail' => array(),
    );

    // Summary result widget.
    $form['result'] = array(
      '#type' => 'fieldset',
      '#title' => t('Results'),
    );
    $form['result']['summary'] = $summary = array(
      '#theme' => 'simpletest_result_summary',
      '#pass' => 0,
      '#fail' => 0,
      '#exception' => 0,
      '#debug' => 0,
    );

    simpletest_classloader_register();

    // Cycle through each test group.
    $header = array(
      t('Message'),
      t('Group'),
      t('Filename'),
      t('Line'),
      t('Function'),
      array('colspan' => 2, 'data' => t('Status'))
    );
    $form['result']['results'] = array();
    foreach ($results as $group => $assertions) {
      // Create group details with summary information.
      $info = call_user_func(array($group, 'getInfo'));
      $form['result']['results'][$group] = array(
        '#type' => 'details',
        '#title' => $info['name'],
        '#description' => $info['description'],
      );
      $form['result']['results'][$group]['summary'] = $summary;
      $group_summary =& $form['result']['results'][$group]['summary'];

      // Create table of assertions for the group.
      $rows = array();
      foreach ($assertions as $assertion) {
        $row = array();
        $row[] = $assertion->message;
        $row[] = $assertion->message_group;
        $row[] = drupal_basename($assertion->file);
        $row[] = $assertion->line;
        $row[] = $assertion->function;
        $row[] = $this->statusImageMap[$assertion->status];

        $class = 'simpletest-' . $assertion->status;
        if ($assertion->message_group == 'Debug') {
          $class = 'simpletest-debug';
        }
        $rows[] = array('data' => $row, 'class' => array($class));

        $group_summary['#' . $assertion->status]++;
        $form['result']['summary']['#' . $assertion->status]++;
      }
      $form['result']['results'][$group]['table'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      );

      // Set summary information.
      $group_summary['#ok'] = $group_summary['#fail'] + $group_summary['#exception'] == 0;
      $form['result']['results'][$group]['#collapsed'] = $group_summary['#ok'];

      // Store test group (class) as for use in filter.
      $filter[$group_summary['#ok'] ? 'pass' : 'fail'][] = $group;
    }

    // Overal summary status.
    $form['result']['summary']['#ok'] = $form['result']['summary']['#fail'] + $form['result']['summary']['#exception'] == 0;

    // Actions.
    $form['#action'] = url('admin/config/development/testing/results/re-run');
    $form['action'] = array(
      '#type' => 'fieldset',
      '#title' => t('Actions'),
      '#attributes' => array('class' => array('container-inline')),
      '#weight' => -11,
    );

    $form['action']['filter'] = array(
      '#type' => 'select',
      '#title' => 'Filter',
      '#options' => array(
        'all' => t('All (@count)', array('@count' => count($filter['pass']) + count($filter['fail']))),
        'pass' => t('Pass (@count)', array('@count' => count($filter['pass']))),
        'fail' => t('Fail (@count)', array('@count' => count($filter['fail']))),
      ),
    );
    $form['action']['filter']['#default_value'] = ($filter['fail'] ? 'fail' : 'all');

    // Categorized test classes for to be used with selected filter value.
    $form['action']['filter_pass'] = array(
      '#type' => 'hidden',
      '#default_value' => implode(',', $filter['pass']),
    );
    $form['action']['filter_fail'] = array(
      '#type' => 'hidden',
      '#default_value' => implode(',', $filter['fail']),
    );

    $form['action']['op'] = array(
      '#type' => 'submit',
      '#value' => t('Run tests'),
    );

    $form['action']['return'] = array(
      '#type' => 'link',
      '#title' => t('Return to list'),
      '#href' => 'admin/config/development/testing',
    );

    if (is_numeric($test_id)) {
      simpletest_clean_results_table($test_id);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $pass = $form_state['values']['filter_pass'] ? explode(',', $form_state['values']['filter_pass']) : array();
    $fail = $form_state['values']['filter_fail'] ? explode(',', $form_state['values']['filter_fail']) : array();

    if ($form_state['values']['filter'] == 'all') {
      $classes = array_merge($pass, $fail);
    }
    elseif ($form_state['values']['filter'] == 'pass') {
      $classes = $pass;
    }
    else {
      $classes = $fail;
    }

    if (!$classes) {
      $form_state['redirect'] = 'admin/config/development/testing';
      return;
    }

    $form_execute = array();
    $form_state_execute = array('values' => array());
    foreach ($classes as $class) {
      $form_state_execute['values'][$class] = 1;
    }

    // Submit the simpletest test form to rerun the tests.
    $simpletest_test_form = new SimpletestTestForm();
    $simpletest_test_form->submitForm($form_execute, $form_state_execute);
    $form_state['redirect'] = $form_state_execute['redirect'];
  }

  /**
   * Get test results for $test_id.
   *
   * @param int $test_id
   *   The test_id to retrieve results of.
   *
   * @return array
   *  Array of results grouped by test_class.
   */
  protected function getResults($test_id) {
    $results = $this->database->select('simpletest')
      ->fields('simpletest')
      ->condition('test_id', $test_id)
      ->orderBy('test_class')
      ->orderBy('message_id')
      ->execute();

    $test_results = array();
    foreach ($results as $result) {
      if (!isset($test_results[$result->test_class])) {
        $test_results[$result->test_class] = array();
      }
      $test_results[$result->test_class][] = $result;
    }

    return $test_results;
  }

}
