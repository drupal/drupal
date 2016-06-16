<?php

namespace Drupal\simpletest\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List tests arranged in groups that can be selected and run.
 */
class SimpletestTestForm extends FormBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Constructs a new SimpletestTestForm.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpletest_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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

    // Do not needlessly re-execute a full test discovery if the user input
    // already contains an explicit list of test classes to run.
    $user_input = $form_state->getUserInput();
    if (!empty($user_input['tests'])) {
      return $form;
    }

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
    $form['tests']['#attached']['drupalSettings']['simpleTest']['images'] = [
      (string) $this->renderer->renderPlain($image_collapsed),
      (string) $this->renderer->renderPlain($image_extended),
    ];

    // Generate the list of tests arranged by group.
    $groups = simpletest_test_get_all();
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
          'class' => array('simpletest-group-select-all'),
        ),
      );
      $form['tests'][$group]['title'] = array(
        // Expand/collapse image.
        '#prefix' => '<div class="simpletest-image" id="simpletest-test-group-' . $group_class . '"></div>',
        '#markup' => '<label for="' . $group_class . '-group-select-all">' . $group . '</label>',
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

      // Cycle through each test within the current group.
      foreach ($tests as $class => $info) {
        $form['tests'][$class] = array(
          '#attributes' => array('class' => array($group_class . '-test', 'js-hide')),
        );
        $form['tests'][$class]['title'] = array(
          '#type' => 'label',
          '#title' => '\\' . $info['name'],
          '#wrapper_attributes' => array(
            'class' => array('simpletest-test-label', 'table-filter-text-source'),
          ),
        );
        $form['tests'][$class]['description'] = array(
          '#prefix' => '<div class="description">',
          '#plain_text' => $info['description'],
          '#suffix' => '</div>',
          '#wrapper_attributes' => array(
            'class' => array('simpletest-test-description', 'table-filter-text-source'),
          ),
        );
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Test discovery does not run upon form submission.
    simpletest_classloader_register();

    // This form accepts arbitrary user input for 'tests'.
    // An invalid value will cause the $class_name lookup below to die with a
    // fatal error. Regular user access mechanisms to this form are intact.
    // The only validation effectively being skipped is the validation of
    // available checkboxes vs. submitted checkboxes.
    // @todo Refactor Form API to allow to POST values without constructing the
    //   entire form more easily, BUT retaining routing access security and
    //   retaining Form API CSRF #token security validation, and without having
    //   to rely on form caching.
    $user_input = $form_state->getUserInput();
    if ($form_state->isValueEmpty('tests') && !empty($user_input['tests'])) {
      $form_state->setValue('tests', $user_input['tests']);
    }

    $tests_list = array_filter($form_state->getValue('tests'));
    if (!empty($tests_list)) {
      $test_id = simpletest_run_tests($tests_list, 'drupal');
      $form_state->setRedirect(
        'simpletest.result_form',
        array('test_id' => $test_id)
      );
    }
  }

}
