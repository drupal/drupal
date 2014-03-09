<?php

/**
 * @file
 * Contains \Drupal\simpletest\Form\SimpletestTestForm.
 */

namespace Drupal\simpletest\Form;

use Drupal\Component\Utility\SortArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormBase;

/**
 * List tests arranged in groups that can be selected and run.
 */
class SimpletestTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpletest_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // JavaScript-only table filters.
    $form['filters'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show'),
      ),
    );
    $form['filters']['text'] = array(
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Enter test name…'),
      '#attributes' => array(
        'class' => array('table-filter-text'),
        'data-table' => '#simpletest-test-form',
        'autocomplete' => 'off',
        'title' => $this->t('Enter at least 3 characters of the test name or description to filter by.'),
      ),
    );

    $form['tests'] = array(
      '#type' => 'table',
      '#id' => 'simpletest-form-table',
      '#tableselect' => TRUE,
      '#header' => array(
        array('data' => $this->t('Test'), 'class' => array('simpletest-test-label')),
        array('data' => $this->t('Description'), 'class' => array('simpletest-test-description')),
      ),
      '#empty' => $this->t('No tests to display.'),
      '#attached' => array(
        'library' => array(
          'simpletest/drupal.simpletest',
        ),
      ),
    );

    // Define the images used to expand/collapse the test groups.
    $image_collapsed = array(
      '#theme' => 'image',
      '#uri' => 'core/misc/menu-collapsed.png',
      '#width' => '7',
      '#height' => '7',
      '#alt' => $this->t('Expand'),
      '#title' => $this->t('Expand'),
      '#suffix' => '<a href="#" class="simpletest-collapse">(' . $this->t('Expand') . ')</a>',
    );
    $image_extended = array(
      '#theme' => 'image',
      '#uri' => 'core/misc/menu-expanded.png',
      '#width' => '7',
      '#height' => '7',
      '#alt' => $this->t('Collapse'),
      '#title' => $this->t('Collapse'),
      '#suffix' => '<a href="#" class="simpletest-collapse">(' . $this->t('Collapse') . ')</a>',
    );
    $js = array(
      'images' => array(
        drupal_render($image_collapsed),
        drupal_render($image_extended),
      ),
    );

    // Generate the list of tests arranged by group.
    $groups = simpletest_test_get_all();
    $groups['PHPUnit'] = simpletest_phpunit_get_available_tests();
    $form_state['storage']['PHPUnit'] = $groups['PHPUnit'];

    foreach ($groups as $group => $tests) {
      $form['tests'][$group] = array(
        '#attributes' => array('class' => array('simpletest-group')),
      );

      // Make the class name safe for output on the page by replacing all
      // non-word/decimal characters with a dash (-).
      $group_class = 'module-' . strtolower(trim(preg_replace("/[^\w\d]/", "-", $group)));

      // Override tableselect column with custom selector for this group.
      // This group-select-all checkbox is injected via JavaScript.
      $form['tests'][$group]['select'] = array(
        '#wrapper_attributes' => array(
          'id' => $group_class,
          'class' => array('simpletest-select-all'),
        ),
      );
      $form['tests'][$group]['title'] = array(
        // Expand/collapse image.
        '#prefix' => '<div class="simpletest-image" id="simpletest-test-group-' . $group_class . '"></div>',
        '#markup' => '<label for="' . $group_class . '-select-all" class="simpletest-group-label">' . $group . '</label>',
        '#wrapper_attributes' => array(
          'class' => array('simpletest-group-label'),
        ),
      );
      $form['tests'][$group]['description'] = array(
        '#markup' => '&nbsp;',
        '#wrapper_attributes' => array(
          'class' => array('simpletest-group-description'),
        ),
      );

      // Add individual tests to group.
      $current_js = array(
        'testClass' => $group_class . '-test',
        'testNames' => array(),
        // imageDirection maps to the 'images' index in the $js array.
        'imageDirection' => 0,
        'clickActive' => FALSE,
      );

      // Sort test classes within group alphabetically by name/label.
      uasort($tests, function ($a, $b) {
        return SortArray::sortByKeyString($a, $b, 'name');
      });

      // Cycle through each test within the current group.
      foreach ($tests as $class => $info) {
        $test_id = drupal_clean_id_identifier($class);
        $test_checkbox_id = 'edit-tests-' . $test_id;
        $current_js['testNames'][] = $test_checkbox_id;

        $form['tests'][$class] = array(
          '#attributes' => array('class' => array($group_class . '-test', 'js-hide')),
        );
        $form['tests'][$class]['title'] = array(
          '#type' => 'label',
          '#title' => $info['name'],
          '#wrapper_attributes' => array(
            'class' => array('simpletest-test-label', 'table-filter-text-source'),
          ),
        );
        $form['tests'][$class]['description'] = array(
          '#prefix' => '<div class="description">',
          '#markup' => String::format('@description (@class)', array(
            '@description' => $info['description'],
            '@class' => $class,
          )),
          '#suffix' => '</div>',
          '#wrapper_attributes' => array(
            'class' => array('simpletest-test-description', 'table-filter-text-source'),
          ),
        );
      }

      $js['simpletest-test-group-' . $group_class] = $current_js;
    }

    // Add JavaScript array of settings.
    $form['tests']['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('simpleTest' => $js),
    );

    // Action buttons.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Run tests'),
      '#tableselect' => TRUE,
      '#button_type' => 'primary',
    );

    $form['clean'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Clean test environment'),
      '#description' => $this->t('Remove tables with the prefix "simpletest" and temporary directories that are left over from tests that crashed. This is intended for developers when creating tests.'),
      '#weight' => 200,
    );
    $form['clean']['op'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clean environment'),
      '#submit' => array('simpletest_clean_environment'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    simpletest_classloader_register();

    $phpunit_all = array_keys($form_state['storage']['PHPUnit']);

    $tests_list = array();
    foreach ($form_state['values']['tests'] as $class_name => $value) {
      // Since class_exists() will likely trigger an autoload lookup,
      // we do the fast check first.
      if ($value === $class_name && class_exists($class_name)) {
        $test_type = in_array($class_name, $phpunit_all) ? 'UnitTest' : 'WebTest';
        $tests_list[$test_type][] = $class_name;
      }
    }
    if (!empty($tests_list)) {
      $test_id = simpletest_run_tests($tests_list, 'drupal');
      $form_state['redirect_route'] = array(
        'route_name' => 'simpletest.result_form',
        'route_parameters' => array(
          'test_id' => $test_id,
        ),
      );
    }
  }

}
