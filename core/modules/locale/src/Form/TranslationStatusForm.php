<?php

namespace Drupal\locale\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a translation status form.
 *
 * @internal
 */
class TranslationStatusForm extends FormBase {

  /**
   * The module handler service.
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * Constructs a TranslationStatusForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, StateInterface $state) {
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locale_translation_status_form';
  }

  /**
   * Form builder for displaying the current translation status.
   *
   * @ingroup forms
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $languages = locale_translatable_language_list();
    $status = locale_translation_get_status();
    $options = [];
    $languages_update = [];
    $languages_not_found = [];
    $projects_update = [];
    // Prepare information about projects which have available translation
    // updates.
    if ($languages && $status) {
      $updates = $this->prepareUpdateData($status);

      // Build data options for the select table.
      foreach ($updates as $langcode => $update) {
        $title = $languages[$langcode]->getName();
        $locale_translation_update_info = ['#theme' => 'locale_translation_update_info'];
        foreach (['updates', 'not_found'] as $update_status) {
          if (isset($update[$update_status])) {
            $locale_translation_update_info['#' . $update_status] = $update[$update_status];
          }
        }
        $options[$langcode] = [
          'title' => [
            'data' => [
              '#title' => $title,
              '#plain_text' => $title,
            ],
          ],
          'status' => [
            'class' => ['description', 'priority-low'],
            'data' => $locale_translation_update_info,
          ],
        ];
        if (!empty($update['not_found'])) {
          $languages_not_found[$langcode] = $langcode;
        }
        if (!empty($update['updates'])) {
          $languages_update[$langcode] = $langcode;
        }
      }
      // Sort the table data on language name.
      uasort($options, function ($a, $b) {
        return strcasecmp($a['title']['data']['#title'], $b['title']['data']['#title']);
      });
      $languages_not_found = array_diff($languages_not_found, $languages_update);
    }

    $last_checked = $this->state->get('locale.translation_last_checked');
    $form['last_checked'] = [
      '#theme' => 'locale_translation_last_check',
      '#last' => $last_checked,
    ];

    $header = [
      'title' => [
        'data' => $this->t('Language'),
        'class' => ['title'],
      ],
      'status' => [
        'data' => $this->t('Status'),
        'class' => ['status', 'priority-low'],
      ],
    ];

    if (!$languages) {
      $empty = $this->t('No translatable languages available. <a href=":add_language">Add a language</a> first.', [
        ':add_language' => Url::fromRoute('entity.configurable_language.collection')->toString(),
      ]);
    }
    elseif ($status) {
      $empty = $this->t('All translations up to date.');
    }
    else {
      $empty = $this->t('No translation status available. <a href=":check">Check manually</a>.', [
        ':check' => Url::fromRoute('locale.check_translation')->toString(),
      ]);
    }

    // The projects which require an update. Used by the _submit callback.
    $form['projects_update'] = [
      '#type' => 'value',
      '#value' => $projects_update,
    ];

    $form['langcodes'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $languages_update,
      '#empty' => $empty,
      '#js_select' => TRUE,
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#not_found' => $languages_not_found,
      '#after_build' => ['locale_translation_language_table'],
    ];

    $form['#attached']['library'][] = 'locale/drupal.locale.admin';

    $form['actions'] = ['#type' => 'actions'];
    if ($languages_update) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update translations'),
      ];
    }

    return $form;
  }

  /**
   * Prepare information about projects with available translation updates.
   *
   * @param array $status
   *   Translation update status as an array keyed by Project ID and langcode.
   *
   * @return array
   *   Translation update status as an array keyed by language code and
   *   translation update status.
   */
  protected function prepareUpdateData(array $status) {
    $updates = [];

    // @todo Calling locale_translation_build_projects() is an expensive way to
    //   get a module name. In follow-up issue
    //   https://www.drupal.org/node/1842362 the project name will be stored to
    //   display use, like here.
    $this->moduleHandler->loadInclude('locale', 'compare.inc');
    $project_data = locale_translation_build_projects();

    foreach ($status as $project_id => $project) {
      foreach ($project as $langcode => $project_info) {
        // No translation file found for this project-language combination.
        if (empty($project_info->type)) {
          $updates[$langcode]['not_found'][] = [
            'name' => $project_info->name == 'drupal' ? $this->t('Drupal core') : $project_data[$project_info->name]->info['name'],
            'version' => $project_info->version,
            'info' => $this->createInfoString($project_info),
          ];
        }
        // Translation update found for this project-language combination.
        elseif ($project_info->type == LOCALE_TRANSLATION_LOCAL || $project_info->type == LOCALE_TRANSLATION_REMOTE) {
          $local = isset($project_info->files[LOCALE_TRANSLATION_LOCAL]) ? $project_info->files[LOCALE_TRANSLATION_LOCAL] : NULL;
          $remote = isset($project_info->files[LOCALE_TRANSLATION_REMOTE]) ? $project_info->files[LOCALE_TRANSLATION_REMOTE] : NULL;
          $recent = _locale_translation_source_compare($local, $remote) == LOCALE_TRANSLATION_SOURCE_COMPARE_LT ? $remote : $local;
          $updates[$langcode]['updates'][] = [
            'name' => $project_info->name == 'drupal' ? $this->t('Drupal core') : $project_data[$project_info->name]->info['name'],
            'version' => $project_info->version,
            'timestamp' => $recent->timestamp,
          ];
        }
      }
    }
    return $updates;
  }

  /**
   * Provides debug info for projects in case translation files are not found.
   *
   * Translations files are being fetched either from Drupal translation server
   * and local files or only from the local filesystem depending on the
   * "Translation source" setting at admin/config/regional/translate/settings.
   * This method will produce debug information including the respective path(s)
   * based on this setting.
   *
   * @param array $project_info
   *   An array which is the project information of the source.
   *
   * @return string
   *   The string which contains debug information.
   */
  protected function createInfoString($project_info) {
    $remote_path = isset($project_info->files['remote']->uri) ? $project_info->files['remote']->uri : FALSE;
    $local_path = isset($project_info->files['local']->uri) ? $project_info->files['local']->uri : FALSE;

    if (locale_translation_use_remote_source() && $remote_path && $local_path) {
      return $this->t('File not found at %remote_path nor at %local_path', [
        '%remote_path' => $remote_path,
        '%local_path' => $local_path,
      ]);
    }
    elseif ($local_path) {
      return $this->t('File not found at %local_path', ['%local_path' => $local_path]);
    }
    return $this->t('Translation file location could not be determined.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if a language has been selected. 'tableselect' doesn't.
    if (!array_filter($form_state->getValue('langcodes'))) {
      $form_state->setErrorByName('', $this->t('Select a language to update.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->moduleHandler->loadInclude('locale', 'fetch.inc');
    $this->moduleHandler->loadInclude('locale', 'bulk.inc');

    $langcodes = array_filter($form_state->getValue('langcodes'));
    $projects = array_filter($form_state->getValue('projects_update'));

    // Set the translation import options. This determines if existing
    // translations will be overwritten by imported strings.
    $options = _locale_translation_default_update_options();

    // If the status was updated recently we can immediately start fetching the
    // translation updates. If the status is expired we clear it an run a batch to
    // update the status and then fetch the translation updates.
    $last_checked = $this->state->get('locale.translation_last_checked');
    if ($last_checked < REQUEST_TIME - LOCALE_TRANSLATION_STATUS_TTL) {
      locale_translation_clear_status();
      $batch = locale_translation_batch_update_build([], $langcodes, $options);
      batch_set($batch);
    }
    else {
      // Set a batch to download and import translations.
      $batch = locale_translation_batch_fetch_build($projects, $langcodes, $options);
      batch_set($batch);
      // Set a batch to update configuration as well.
      if ($batch = locale_config_batch_update_components($options, $langcodes)) {
        batch_set($batch);
      }
    }
  }

}
