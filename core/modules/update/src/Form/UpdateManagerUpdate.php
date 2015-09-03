<?php

/**
 * @file
 * Contains \Drupal\update\Form\UpdateManagerUpdate.
 */

namespace Drupal\update\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure update settings for this site.
 */
class UpdateManagerUpdate extends FormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new UpdateManagerUpdate object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *  The state service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, StateInterface $state) {
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_manager_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->moduleHandler->loadInclude('update', 'inc', 'update.manager');

    $last_markup = array(
      '#theme' => 'update_last_check',
      '#last' => $this->state->get('update.last_check') ?: 0,
    );
    $form['last_check'] = array(
      '#markup' => drupal_render($last_markup),
    );

    if (!_update_manager_check_backends($form, 'update')) {
      return $form;
    }

    $available = update_get_available(TRUE);
    if (empty($available)) {
      $form['message'] = array(
        '#markup' => $this->t('There was a problem getting update information. Try again later.'),
      );
      return $form;
    }

    $form['#attached']['library'][] = 'update/drupal.update.admin';

    // This will be a nested array. The first key is the kind of project, which
    // can be either 'enabled', 'disabled', 'manual' (projects which require
    // manual updates, such as core). Then, each subarray is an array of
    // projects of that type, indexed by project short name, and containing an
    // array of data for cells in that project's row in the appropriate table.
    $projects = array();

    // This stores the actual download link we're going to update from for each
    // project in the form, regardless of if it's enabled or disabled.
    $form['project_downloads'] = array('#tree' => TRUE);
    $this->moduleHandler->loadInclude('update', 'inc', 'update.compare');
    $project_data = update_calculate_project_data($available);
    foreach ($project_data as $name => $project) {
      // Filter out projects which are up to date already.
      if ($project['status'] == UPDATE_CURRENT) {
        continue;
      }
      // The project name to display can vary based on the info we have.
      if (!empty($project['title'])) {
        if (!empty($project['link'])) {
          $project_name = $this->l($project['title'], Url::fromUri($project['link']));
        }
        else {
          $project_name = SafeMarkup::checkPlain($project['title']);
        }
      }
      elseif (!empty($project['info']['name'])) {
        $project_name = SafeMarkup::checkPlain($project['info']['name']);
      }
      else {
        $project_name = SafeMarkup::checkPlain($name);
      }
      if ($project['project_type'] == 'theme' || $project['project_type'] == 'theme-disabled') {
        $project_name .= ' ' . $this->t('(Theme)');
      }

      if (empty($project['recommended'])) {
        // If we don't know what to recommend they upgrade to, we should skip
        // the project entirely.
        continue;
      }

      $recommended_release = $project['releases'][$project['recommended']];
      $recommended_version = '{{ release_version }} (<a href="{{ release_link }}" title="{{ project_title }}">{{ release_notes }}</a>)';
      if ($recommended_release['version_major'] != $project['existing_major']) {
        $recommended_version .= '<div title="{{ major_update_warning_title }}" class="update-major-version-warning">{{ major_update_warning_text }}</div>';
      }

      $recommended_version = array(
        '#type' => 'inline_template',
        '#template' => $recommended_version,
        '#context' => array(
          'release_version' => $recommended_release['version'],
          'release_link' => $recommended_release['release_link'],
          'project_title' => $this->t('Release notes for @project_title', array('@project_title' => $project['title'])),
          'major_update_warning_title' => $this->t('Major upgrade warning'),
          'major_update_warning_text' => $this->t('This update is a major version update which means that it may not be backwards compatible with your currently running version. It is recommended that you read the release notes and proceed at your own risk.'),
          'release_notes' => $this->t('Release notes'),
        ),
      );

      // Create an entry for this project.
      $entry = array(
        'title' => $project_name,
        'installed_version' => $project['existing_version'],
        'recommended_version' => array('data' => $recommended_version),
      );

      switch ($project['status']) {
        case UPDATE_NOT_SECURE:
        case UPDATE_REVOKED:
          $entry['title'] .= ' ' . $this->t('(Security update)');
          $entry['#weight'] = -2;
          $type = 'security';
          break;

        case UPDATE_NOT_SUPPORTED:
          $type = 'unsupported';
          $entry['title'] .= ' ' . $this->t('(Unsupported)');
          $entry['#weight'] = -1;
          break;

        case UPDATE_UNKNOWN:
        case UPDATE_NOT_FETCHED:
        case UPDATE_NOT_CHECKED:
        case UPDATE_NOT_CURRENT:
          $type = 'recommended';
          break;

        default:
          // Jump out of the switch and onto the next project in foreach.
          continue 2;
      }

      // Use the project title for the tableselect checkboxes.
      $entry['title'] = array('data' => array(
        '#title' => $entry['title'],
        '#markup' => $entry['title'],
      ));
      $entry['#attributes'] = array('class' => array('update-' . $type));

      // Drupal core needs to be upgraded manually.
      $needs_manual = $project['project_type'] == 'core';

      if ($needs_manual) {
        // There are no checkboxes in the 'Manual updates' table so it will be
        // rendered by _theme('table'), not _theme('tableselect'). Since the data
        // formats are incompatible, we convert now to the format expected by
        // _theme('table').
        unset($entry['#weight']);
        $attributes = $entry['#attributes'];
        unset($entry['#attributes']);
        $entry = array(
          'data' => $entry,
        ) + $attributes;
      }
      else {
        $form['project_downloads'][$name] = array(
          '#type' => 'value',
          '#value' => $recommended_release['download_link'],
        );
      }

      // Based on what kind of project this is, save the entry into the
      // appropriate subarray.
      switch ($project['project_type']) {
        case 'core':
          // Core needs manual updates at this time.
          $projects['manual'][$name] = $entry;
          break;

        case 'module':
        case 'theme':
          $projects['enabled'][$name] = $entry;
          break;

        case 'module-disabled':
        case 'theme-disabled':
          $projects['disabled'][$name] = $entry;
          break;
      }
    }

    if (empty($projects)) {
      $form['message'] = array(
        '#markup' => $this->t('All of your projects are up to date.'),
      );
      return $form;
    }

    $headers = array(
      'title' => array(
        'data' => $this->t('Name'),
        'class' => array('update-project-name'),
      ),
      'installed_version' => $this->t('Installed version'),
      'recommended_version' => $this->t('Recommended version'),
    );

    if (!empty($projects['enabled'])) {
      $form['projects'] = array(
        '#type' => 'tableselect',
        '#header' => $headers,
        '#options' => $projects['enabled'],
      );
      if (!empty($projects['disabled'])) {
        $form['projects']['#prefix'] = '<h2>' . $this->t('Enabled') . '</h2>';
      }
    }

    if (!empty($projects['disabled'])) {
      $form['disabled_projects'] = array(
        '#type' => 'tableselect',
        '#header' => $headers,
        '#options' => $projects['disabled'],
        '#weight' => 1,
        '#prefix' => '<h2>' . $this->t('Disabled') . '</h2>',
      );
    }

    // If either table has been printed yet, we need a submit button and to
    // validate the checkboxes.
    if (!empty($projects['enabled']) || !empty($projects['disabled'])) {
      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Download these updates'),
      );
    }

    if (!empty($projects['manual'])) {
      $prefix = '<h2>' . $this->t('Manual updates required') . '</h2>';
      $prefix .= '<p>' . $this->t('Updates of Drupal core are not supported at this time.') . '</p>';
      $form['manual_updates'] = array(
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => $projects['manual'],
        '#prefix' => $prefix,
        '#weight' => 120,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('projects')) {
      $enabled = array_filter($form_state->getValue('projects'));
    }
    if (!$form_state->isValueEmpty('disabled_projects')) {
      $disabled = array_filter($form_state->getValue('disabled_projects'));
    }
    if (empty($enabled) && empty($disabled)) {
      $form_state->setErrorByName('projects', $this->t('You must select at least one project to update.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->moduleHandler->loadInclude('update', 'inc', 'update.manager');
    $projects = array();
    foreach (array('projects', 'disabled_projects') as $type) {
      if (!$form_state->isValueEmpty($type)) {
        $projects = array_merge($projects, array_keys(array_filter($form_state->getValue($type))));
      }
    }
    $operations = array();
    foreach ($projects as $project) {
      $operations[] = array(
        'update_manager_batch_project_get',
        array(
          $project,
          $form_state->getValue(array('project_downloads', $project)),
        ),
      );
    }
    $batch = array(
      'title' => $this->t('Downloading updates'),
      'init_message' => $this->t('Preparing to download selected updates'),
      'operations' => $operations,
      'finished' => 'update_manager_download_batch_finished',
      'file' => drupal_get_path('module', 'update') . '/update.manager.inc',
    );
    batch_set($batch);
  }

}
