<?php

/**
 * @file
 * Contains \Drupal\update\Form\UpdateManagerInstall.
 */

namespace Drupal\update\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\FileTransfer\Local;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Updater\Updater;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Configure update settings for this site.
 */
class UpdateManagerInstall extends FormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The root location under which installed projects will be saved.
   *
   * @var string
   */
  protected $root;

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * Constructs a new UpdateManagerInstall.
   *
   * @param string $root
   *   The root location under which installed projects will be saved.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string $site_path
   *   The site path.
   */
  public function __construct($root, ModuleHandlerInterface $module_handler, $site_path) {
    $this->root = $root;
    $this->moduleHandler = $module_handler;
    $this->sitePath = $site_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_manager_install_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('update.root'),
      $container->get('module_handler'),
      $container->get('site.path')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->moduleHandler->loadInclude('update', 'inc', 'update.manager');
    if (!_update_manager_check_backends($form, 'install')) {
      return $form;
    }

    $form['help_text'] = array(
      '#prefix' => '<p>',
      '#markup' => $this->t('You can find <a href=":module_url">modules</a> and <a href=":theme_url">themes</a> on <a href=":drupal_org_url">drupal.org</a>. The following file extensions are supported: %extensions.', array(
        ':module_url' => 'https://www.drupal.org/project/modules',
        ':theme_url' => 'https://www.drupal.org/project/themes',
        ':drupal_org_url' => 'https://www.drupal.org',
        '%extensions' => archiver_get_extensions(),
      )),
      '#suffix' => '</p>',
    );

    $form['project_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('Install from a URL'),
      '#description' => $this->t('For example: %url', array('%url' => 'http://ftp.drupal.org/files/projects/name.tar.gz')),
    );

    $form['information'] = array(
      '#prefix' => '<strong>',
      '#markup' => $this->t('Or'),
      '#suffix' => '</strong>',
    );

    $form['project_upload'] = array(
      '#type' => 'file',
      '#title' => $this->t('Upload a module or theme archive to install'),
      '#description' => $this->t('For example: %filename from your local computer', array('%filename' => 'name.tar.gz')),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Install'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $uploaded_file = $this->getRequest()->files->get('files[project_upload]', NULL, TRUE);
    if (!($form_state->getValue('project_url') XOR !empty($uploaded_file))) {
      $form_state->setErrorByName('project_url', $this->t('You must either provide a URL or upload an archive file to install.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $local_cache = NULL;
    if ($form_state->getValue('project_url')) {
      $local_cache = update_manager_file_get($form_state->getValue('project_url'));
      if (!$local_cache) {
        drupal_set_message($this->t('Unable to retrieve Drupal project from %url.', array('%url' => $form_state->getValue('project_url'))), 'error');
        return;
      }
    }
    elseif ($_FILES['files']['name']['project_upload']) {
      $validators = array('file_validate_extensions' => array(archiver_get_extensions()));
      if (!($finfo = file_save_upload('project_upload', $validators, NULL, 0, FILE_EXISTS_REPLACE))) {
        // Failed to upload the file. file_save_upload() calls
        // drupal_set_message() on failure.
        return;
      }
      $local_cache = $finfo->getFileUri();
    }

    $directory = _update_manager_extract_directory();
    try {
      $archive = update_manager_archive_extract($local_cache, $directory);
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return;
    }

    $files = $archive->listContents();
    if (!$files) {
      drupal_set_message($this->t('Provided archive contains no files.'), 'error');
      return;
    }

    // Unfortunately, we can only use the directory name to determine the
    // project name. Some archivers list the first file as the directory (i.e.,
    // MODULE/) and others list an actual file (i.e., MODULE/README.TXT).
    $project = strtok($files[0], '/\\');

    $archive_errors = $this->moduleHandler->invokeAll('verify_update_archive', array($project, $local_cache, $directory));
    if (!empty($archive_errors)) {
      drupal_set_message(array_shift($archive_errors), 'error');
      // @todo: Fix me in D8: We need a way to set multiple errors on the same
      // form element and have all of them appear!
      if (!empty($archive_errors)) {
        foreach ($archive_errors as $error) {
          drupal_set_message($error, 'error');
        }
      }
      return;
    }

    // Make sure the Updater registry is loaded.
    drupal_get_updaters();

    $project_location = $directory . '/' . $project;
    try {
      $updater = Updater::factory($project_location, $this->root);
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return;
    }

    try {
      $project_title = Updater::getProjectTitle($project_location);
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return;
    }

    if (!$project_title) {
      drupal_set_message($this->t('Unable to determine %project name.', array('%project' => $project)), 'error');
    }

    if ($updater->isInstalled()) {
      drupal_set_message($this->t('%project is already installed.', array('%project' => $project_title)), 'error');
      return;
    }

    $project_real_location = drupal_realpath($project_location);
    $arguments = array(
      'project' => $project,
      'updater_name' => get_class($updater),
      'local_url' => $project_real_location,
    );

    // If the owner of the directory we extracted is the same as the owner of
    // our configuration directory (e.g. sites/default) where we're trying to
    // install the code, there's no need to prompt for FTP/SSH credentials.
    // Instead, we instantiate a Drupal\Core\FileTransfer\Local and invoke
    // update_authorize_run_install() directly.
    if (fileowner($project_real_location) == fileowner($this->sitePath)) {
      $this->moduleHandler->loadInclude('update', 'inc', 'update.authorize');
      $filetransfer = new Local($this->root);
      $response = call_user_func_array('update_authorize_run_install', array_merge(array($filetransfer), $arguments));
      if ($response instanceof Response) {
        $form_state->setResponse($response);
      }
    }

    // Otherwise, go through the regular workflow to prompt for FTP/SSH
    // credentials and invoke update_authorize_run_install() indirectly with
    // whatever FileTransfer object authorize.php creates for us.
    else {
      // The page title must be passed here to ensure it is initially used when
      // authorize.php loads for the first time with the FTP/SSH credentials
      // form.
      system_authorized_init('update_authorize_run_install', drupal_get_path('module', 'update') . '/update.authorize.inc', $arguments, $this->t('Update manager'));
      $form_state->setRedirectUrl(system_authorized_get_url());
    }
  }

}
