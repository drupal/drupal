<?php
// $Id: update.manager.inc,v 1.23 2010/05/26 11:50:58 dries Exp $

/**
 * @file
 * Administrative screens and processing functions for the update manager.
 * This allows site administrators with the 'administer software updates'
 * permission to either upgrade existing projects, or download and install new
 * ones, so long as the killswitch setting ('allow_authorize_operations') is
 * still TRUE.
 *
 * To install new code, the administrator is prompted for either the URL of an
 * archive file, or to directly upload the archive file. The archive is loaded
 * into a temporary location, extracted, and verified. If everything is
 * successful, the user is redirected to authorize.php to type in their file
 * transfer credentials and authorize the installation to proceed with
 * elevated privileges, such that the extracted files can be copied out of the
 * temporary location and into the live web root.
 *
 * Updating existing code is a more elaborate process. The first step is a
 * selection form where the user is presented with a table of installed
 * projects that are missing newer releases. The user selects which projects
 * they wish to upgrade, and presses the "Download updates" button to
 * continue. This sets up a batch to fetch all the selected releases, and
 * redirects to admin/update/download to display the batch progress bar as it
 * runs. Each batch operation is responsible for downloading a single file,
 * extracting the archive, and verifying the contents. If there are any
 * errors, the user is redirected back to the first page with the error
 * messages. If all downloads were extacted and verified, the user is instead
 * redirected to admin/update/ready, a landing page which reminds them to
 * backup their database and asks if they want to put the site offline during
 * the upgrade. Once the user presses the "Install updates" button, they are
 * redirected to authorize.php to supply their web root file access
 * credentials. The authorized operation (which lives in update.authorize.inc)
 * sets up a batch to copy each extracted update from the temporary location
 * into the live web root.
 */

/**
 * @defgroup update_manager_update Update manager for updating existing code.
 * @{
 */

/**
 * Build the form for the update manager page to update existing projects.
 *
 * This presents a table with all projects that have available updates with
 * checkboxes to select which ones to upgrade.
 *
 * @param $form
 * @param $form_state
 * @param $context
 *   String representing the context from which we're trying to update, can be:
 *   'module', 'theme' or 'report'.
 * @return
 *   The form array for selecting which projects to update.
 */
function update_manager_update_form($form, $form_state = array(), $context) {
  $form['#theme'] = 'update_manager_update_form';

  $available = update_get_available(TRUE);
  if (empty($available)) {
    $form['message'] = array(
      '#markup' => t('There was a problem getting update information. Try again later.'),
    );
    return $form;
  }

  $form['#attached']['css'][] = drupal_get_path('module', 'update') . '/update.css';

  // This will be a nested array. The first key is the kind of project, which
  // can be either 'enabled', 'disabled', 'manual' (projects which require
  // manual updates, such as core). Then, each subarray is an array of
  // projects of that type, indexed by project short name, and containing an
  // array of data for cells in that project's row in the appropriate table.
  $projects = array();

  // This stores the actual download link we're going to update from for each
  // project in the form, regardless of if it's enabled or disabled.
  $form['project_downloads'] = array('#tree' => TRUE);

  module_load_include('inc', 'update', 'update.compare');
  $project_data = update_calculate_project_data($available);
  foreach ($project_data as $name => $project) {
    // Filter out projects which are up to date already.
    if ($project['status'] == UPDATE_CURRENT) {
      continue;
    }
    // The project name to display can vary based on the info we have.
    if (!empty($project['title'])) {
      if (!empty($project['link'])) {
        $project_name = l($project['title'], $project['link']);
      }
      else {
        $project_name = check_plain($project['title']);
      }
    }
    elseif (!empty($project['info']['name'])) {
      $project_name = check_plain($project['info']['name']);
    }
    else {
      $project_name = check_plain($name);
    }
    if ($project['project_type'] == 'theme' || $project['project_type'] == 'theme-disabled') {
      $project_name .= ' ' . t('(Theme)');
    }

    if (empty($project['recommended'])) {
      // If we don't know what to recommend they upgrade to, we should skip
      // the project entirely.
      continue;
    }

    $recommended_release = $project['releases'][$project['recommended']];
    $recommended_version = $recommended_release['version'] . ' ' . l(t('(Release notes)'), $recommended_release['release_link'], array('attributes' => array('title' => t('Release notes for @project_name', array('@project_name' => $project_name)))));
    if ($recommended_release['version_major'] != $project['existing_major']) {
      $recommended_version .= '<div title="Major upgrade warning" class="update-major-version-warning">' . t('This update is a major version update which means that it may not be backwards compatible with your currently running version.  It is recommended that you read the release notes and proceed at your own risk.') . '</div>';
    }

    // Create an entry for this project.
    $entry = array(
      'title' => $project_name,
      'installed_version' => $project['existing_version'],
      'recommended_version' => $recommended_version,
    );

    switch ($project['status']) {
      case UPDATE_NOT_SECURE:
      case UPDATE_REVOKED:
        $entry['title'] .= ' ' . t('(Security update)');
        $entry['#weight'] = -2;
        $type = 'security';
        break;

      case UPDATE_NOT_SUPPORTED:
        $type = 'unsupported';
        $entry['title'] .= ' ' . t('(Unsupported)');
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

    $entry['#attributes'] = array('class' => array('update-' . $type));

    // Drupal core needs to be upgraded manually.
    $needs_manual = $project['project_type'] == 'core';

    if ($needs_manual) {
      // Since it won't be tableselect, #weight will add an extra column to the
      // table if it's defined, so just unset it. The order doesn't matter that
      // much in the manual updates table, anyway.
      unset($entry['#weight']);
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
      '#markup' => t('All of your projects are up to date.'),
    );
    return $form;
  }

  $headers = array(
    'title' => array(
      'data' => t('Name'),
      'class' => array('update-project-name'),
    ),
    'installed_version' => t('Installed version'),
    'recommended_version' => t('Recommended version'),
  );

  if (!empty($projects['enabled'])) {
    $form['projects'] = array(
      '#type' => 'tableselect',
      '#header' => $headers,
      '#options' => $projects['enabled'],
    );
    if (!empty($projects['disabled'])) {
      $form['projects']['#prefix'] = '<h2>' . t('Enabled') . '</h2>';
    }
  }

  if (!empty($projects['disabled'])) {
    $form['disabled_projects'] = array(
      '#type' => 'tableselect',
      '#header' => $headers,
      '#options' => $projects['disabled'],
      '#weight' => 1,
      '#prefix' => '<h2>' . t('Disabled') . '</h2>',
    );
  }

  // If either table has been printed yet, we need a submit button and to
  // validate the checkboxes.
  if (!empty($projects['enabled']) || !empty($projects['disabled'])) {
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Download these updates'),
    );
    $form['#validate'][] = 'update_manager_update_form_validate';
  }

  if (!empty($projects['manual'])) {
    $prefix = '<h2>' . t('Manual updates required') . '</h2>';
    $prefix .= '<p>' . t('Updates of Drupal core are not supported at this time.') . '</p>';
    $form['manual_updates'] = array(
      '#type' => 'markup',
      '#markup' => theme('table', array('header' => $headers, 'rows' => $projects['manual'])),
      '#prefix' => $prefix,
      '#weight' => 120,
    );
  }

  return $form;
}

/**
 * Returns HTML for the first page in the update manager wizard to select projects.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_update_manager_update_form($variables) {
  $form = $variables['form'];
  $last = variable_get('update_last_check', 0);
  $output = theme('update_last_check', array('last' => $last));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Validation callback to ensure that at least one project is selected.
 */
function update_manager_update_form_validate($form, &$form_state) {
  if (!empty($form_state['values']['projects'])) {
    $enabled = array_filter($form_state['values']['projects']);
  }
  if (!empty($form_state['values']['disabled_projects'])) {
    $disabled = array_filter($form_state['values']['disabled_projects']);
  }
  if (empty($enabled) && empty($disabled)) {
    form_set_error('projects', t('You must select at least one project to update.'));
  }
}

/**
 * Submit function for the main update form.
 *
 * This sets up a batch to download, extract and verify the selected releases
 *
 * @see update_manager_update_form()
 */
function update_manager_update_form_submit($form, &$form_state) {
  $projects = array();
  foreach (array('projects', 'disabled_projects') as $type) {
    if (!empty($form_state['values'][$type])) {
      $projects = array_merge($projects, array_keys(array_filter($form_state['values'][$type])));
    }
  }
  $operations = array();
  foreach ($projects as $project) {
    $operations[] = array(
      'update_manager_batch_project_get',
      array(
        $project,
        $form_state['values']['project_downloads'][$project],
      ),
    );
  }
  $batch = array(
    'title' => t('Downloading updates'),
    'init_message' => t('Preparing to download selected updates'),
    'operations' => $operations,
    'finished' => 'update_manager_download_batch_finished',
    'file' => drupal_get_path('module', 'update') . '/update.manager.inc',
  );
  batch_set($batch);
}

/**
 * Batch callback invoked when the download batch is completed.
 */
function update_manager_download_batch_finished($success, $results) {
  if (!empty($results['errors'])) {
    $error_list = array(
      'title' => t('Downloading updates failed:'),
      'items' => $results['errors'],
    );
    drupal_set_message(theme('item_list', $error_list), 'error');
  }
  elseif ($success) {
    drupal_set_message(t('Updates downloaded successfully.'));
    $_SESSION['update_manager_update_projects'] = $results['projects'];
    drupal_goto('admin/update/ready');
  }
  else {
    // Ideally we're catching all Exceptions, so they should never see this,
    // but just in case, we have to tell them something.
    drupal_set_message(t('Fatal error trying to download.'), 'error');
  }
}

/**
 * Build the form when the site is ready to update (after downloading).
 *
 * This form is an intermediary step in the automated update workflow. It is
 * presented to the site administrator after all the required updates have
 * been downloaded and verified. The point of this page is to encourage the
 * user to backup their site, gives them the opportunity to put the site
 * offline, and then asks them to confirm that the update should continue.
 * After this step, the user is redirected to authorize.php to enter their
 * file transfer credentials and attempt to complete the update.
 */
function update_manager_update_ready_form($form, &$form_state) {
  $form['backup'] = array(
    '#prefix' => '<strong>',
    '#markup' => t('Back up your database and site before you continue. <a href="@backup_url">Learn how</a>.', array('@backup_url' => url('http://drupal.org/node/22281'))),
    '#suffix' => '</strong>',
  );

  $form['site_offline'] = array(
    '#title' => t('Perform updates with site in maintenance mode (strongly recommended)'),
    '#type' => 'checkbox',
    '#default_value' => TRUE,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Continue'),
  );

  return $form;
}

/**
 * Submit handler for the form to confirm that an update should continue.
 *
 * If the site administrator requested that the site is put offline during the
 * update, do so now. Otherwise, pull information about all the required
 * updates out of the SESSION, figure out what Updater class is needed for
 * each one, generate an array of update operations to perform, and hand it
 * all off to system_authorized_init(), then redirect to authorize.php.
 *
 * @see update_authorize_run_update()
 * @see system_authorized_init()
 * @see system_authorized_get_url()
 */
function update_manager_update_ready_form_submit($form, &$form_state) {
  if ($form_state['values']['site_offline'] == TRUE) {
    variable_set('site_offline', TRUE);
  }

  if (!empty($_SESSION['update_manager_update_projects'])) {
    // Make sure the Updater registry is loaded.
    drupal_get_updaters();

    $updates = array();
    $directory = _update_manager_extract_directory();

    $projects = $_SESSION['update_manager_update_projects'];
    unset($_SESSION['update_manager_update_projects']);

    foreach ($projects as $project => $url) {
      $project_location = $directory . '/' . $project;
      $updater = Updater::factory($project_location);
      $project_real_location = drupal_realpath($project_location);
      $updates[] = array(
        'project' => $project,
        'updater_name' => get_class($updater),
        'local_url' => $project_real_location,
      );
    }

    // If the owner of the last directory we extracted is the same as the
    // owner of our configuration directory (e.g. sites/default) where we're
    // trying to install the code, there's no need to prompt for FTP/SSH
    // credentials. Instead, we instantiate a FileTransferLocal and invoke
    // update_authorize_run_update() directly.
    if (fileowner($project_real_location) == fileowner(conf_path())) {
      module_load_include('inc', 'update', 'update.authorize');
      $filetransfer = new FileTransferLocal(DRUPAL_ROOT);
      update_authorize_run_update($filetransfer, $updates);
    }
    // Otherwise, go through the regular workflow to prompt for FTP/SSH
    // credentials and invoke update_authorize_run_update() indirectly with
    // whatever FileTransfer object authorize.php creates for us.
    else {
      system_authorized_init('update_authorize_run_update', drupal_get_path('module', 'update') . '/update.authorize.inc', array($updates));
      $form_state['redirect'] = system_authorized_get_url();
    }
  }
}

/**
 * @} End of "defgroup update_manager_update".
 */

/**
 * @defgroup update_manager_install Update manager for installing new code.
 * @{
 */

/**
 * Build the form for the update manager page to install new projects.
 *
 * This presents a place to enter a URL or upload an archive file to use to
 * install a new module or theme.
 *
 * @param $form
 * @param $form_state
 * @param $context
 *   String representing the context from which we're trying to install, can
 *   be: 'module', 'theme' or 'report'.
 * @return
 *   The form array for selecting which project to install.
 */
function update_manager_install_form($form, &$form_state, $context) {
  $form = array();

  // Collect all the supported archive file extensions for the UI text.
  $extensions = array();
  $archiver_info = archiver_get_info();
  foreach ($archiver_info as $info) {
    if (!empty($info['extensions'])) {
      $extensions += $info['extensions'];
    }
  }
  $form['help_text'] = array(
    '#prefix' => '<p>',
    '#markup' => t('To install a new module or theme, either enter the URL of an archive file you wish to install, or upload the archive file that you have downloaded. You can find <a href="@module_url">modules</a> and <a href="@theme_url">themes</a> at <a href="@drupal_org_url">http://drupal.org</a>. The following archive extensions are supported: %extensions', array('@module_url' => 'http://drupal.org/project/modules', '@theme_url' => 'http://drupal.org/project/themes', '@drupal_org_url' => 'http://drupal.org', '%extensions' => implode(', ', $extensions))),
    '#suffix' => '</p>',
  );

  $form['project_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Install from a URL'),
    '#description' => t('For example: %url', array('%url' => 'http://ftp.drupal.org/files/projects/name.tar.gz')),
  );

  $form['information'] = array(
    '#prefix' => '<strong>',
    '#markup' => t('Or'),
    '#suffix' => '</strong>',
  );

  $form['project_upload'] = array(
    '#type' => 'file',
    '#title' => t('Upload a module or theme archive to install'),
    '#description' => t('For example: %filename from your local computer', array('%filename' => 'name.tar.gz')),
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Install'),
  );

  return $form;
}

/**
 * Validate the form for installing a new project via the update manager.
 */
function update_manager_install_form_validate($form, &$form_state) {
  if (!($form_state['values']['project_url'] XOR !empty($_FILES['files']['name']['project_upload']))) {
    form_set_error('project_url', t('You must either provide a URL or upload an archive file to install.'));
  }

  if ($form_state['values']['project_url']) {
    if (!valid_url($form_state['values']['project_url'], TRUE)) {
      form_set_error('project_url', t('The provided URL is invalid.'));
    }
  }
}

/**
 * Handle form submission when installing new projects via the update manager.
 *
 * Either downloads the file specified in the URL to a temporary cache, or
 * uploads the file attached to the form, then attempts to extract the archive
 * into a temporary location and verify it. Instantiate the appropriate
 * Updater class for this project and make sure it is not already installed in
 * the live webroot. If everything is successful, setup an operation to run
 * via authorize.php which will copy the extracted files from the temporary
 * location into the live site.
 *
 * @see update_authorize_run_install()
 * @see system_authorized_init()
 * @see system_authorized_get_url()
 */
function update_manager_install_form_submit($form, &$form_state) {
  if ($form_state['values']['project_url']) {
    $field = 'project_url';
    $local_cache = update_manager_file_get($form_state['values']['project_url']);
    if (!$local_cache) {
      form_set_error($field, t('Unable to retrieve Drupal project from %url.', array('%url' => $form_state['values']['project_url'])));
      return;
    }
  }
  elseif ($_FILES['files']['name']['project_upload']) {
    $field = 'project_upload';
    // @todo: add some validators here.
    $finfo = file_save_upload($field, array(), NULL, FILE_EXISTS_REPLACE);
    // @todo: find out if the module is already instealled, if so, throw an error.
    $local_cache = $finfo->uri;
  }

  $directory = _update_manager_extract_directory();
  try {
    $archive = update_manager_archive_extract($local_cache, $directory);
  }
  catch (Exception $e) {
    form_set_error($field, $e->getMessage());
    return;
  }

  $files = $archive->listContents();
  if (!$files) {
    form_set_error($field, t('Provided archive contains no files.'));
    return;
  }
  // Unfortunately, we can only use the directory name for this. :(
  $project = drupal_substr($files[0], 0, -1);

  try {
    update_manager_archive_verify($project, $local_cache, $directory);
  }
  catch (Exception $e) {
    form_set_error($field, $e->getMessage());
    return;
  }

  // Make sure the Updater registry is loaded.
  drupal_get_updaters();

  $project_location = $directory . '/' . $project;
  $updater = Updater::factory($project_location);
  $project_title = Updater::getProjectTitle($project_location);

  if (!$project_title) {
    form_set_error($field, t('Unable to determine %project name.', array('%project' => $project)));
  }

  if ($updater->isInstalled()) {
    form_set_error($field, t('%project is already installed.', array('%project' => $project_title)));
    return;
  }

  $project_real_location = drupal_realpath($project_location);
  $arguments = array(
    'project' => $project,
    'updater_name' => get_class($updater),
    'local_url' => $project_real_location,
  );

  // If the owner of the directory we extracted is the same as the
  // owner of our configuration directory (e.g. sites/default) where we're
  // trying to install the code, there's no need to prompt for FTP/SSH
  // credentials. Instead, we instantiate a FileTransferLocal and invoke
  // update_authorize_run_install() directly.
  if (fileowner($project_real_location) == fileowner(conf_path())) {
    module_load_include('inc', 'update', 'update.authorize');
    $filetransfer = new FileTransferLocal(DRUPAL_ROOT);
    call_user_func_array('update_authorize_run_install', array_merge(array($filetransfer), $arguments));
  }
  // Otherwise, go through the regular workflow to prompt for FTP/SSH
  // credentials and invoke update_authorize_run_install() indirectly with
  // whatever FileTransfer object authorize.php creates for us.
  else {
    system_authorized_init('update_authorize_run_install', drupal_get_path('module', 'update') . '/update.authorize.inc', $arguments);
    $form_state['redirect'] = system_authorized_get_url();
  }
}

/**
 * @} End of "defgroup update_manager_install".
 */

/**
 * @defgroup update_manager_file Update manager file management functions.
 * @{
 */

/**
 * Return the directory where update archive files should be extracted.
 *
 * If the directory does not already exist, attempt to create it.
 *
 * @return
 *   The full path to the temporary directory where update file archives
 *   should be extracted.
 */
function _update_manager_extract_directory() {
  $directory = &drupal_static(__FUNCTION__, '');
  if (empty($directory)) {
    $directory = 'temporary://update-extraction';
    if (!file_exists($directory)) {
      mkdir($directory);
    }
  }
  return $directory;
}

/**
 * Unpack a downloaded archive file.
 *
 * @param string $project
 *   The short name of the project to download.
 * @param string $file
 *   The filename of the archive you wish to extract.
 * @param string $directory
 *   The directory you wish to extract the archive into.
 * @return Archiver
 *   The Archiver object used to extract the archive.
 * @throws Exception on failure.
 */
function update_manager_archive_extract($file, $directory) {
  $archiver = archiver_get_archiver($file);
  if (!$archiver) {
    throw new Exception(t('Cannot extract %file, not a valid archive.', array ('%file' => $file)));
  }
  $archiver->extract($directory);
  return $archiver;
}

/**
 * Verify an archive after it has been downloaded and extracted.
 *
 * This function is responsible for invoking hook_verify_update_archive().
 *
 * @param string $project
 *   The short name of the project to download.
 * @param string $archive_file
 *   The filename of the unextracted archive.
 * @param string $directory
 *   The directory that the archive was extracted into.
 *
 * @return void
 * @throws Exception on failure.
 *
 */
function update_manager_archive_verify($project, $archive_file, $directory) {
  $failures = module_invoke_all('verify_update_archive', $project, $archive_file, $directory);
  if (!empty($failures)) {
    throw new Exception(t('Unable to extract %file', array('%file' => $archive_file)));
  }
}

/**
 * Copies a file from $url to the temporary directory for updates.
 *
 * If the file has already been downloaded, returns the the local path.
 *
 * @param $url
 *   The URL of the file on the server.
 *
 * @return string
 *   Path to local file.
 */
function update_manager_file_get($url) {
  $parsed_url = parse_url($url);
  $remote_schemes = array('http', 'https', 'ftp', 'ftps', 'smb', 'nfs');
  if (!in_array($parsed_url['scheme'], $remote_schemes)) {
    // This is a local file, just return the path.
    return drupal_realpath($url);
  }

  // Check the cache and download the file if needed.
  $cache_directory = 'temporary://update-cache';
  $local = $cache_directory . '/' . basename($parsed_url['path']);

  if (!file_exists($cache_directory)) {
    mkdir($cache_directory);
  }

  if (!file_exists($local)) {
    return system_retrieve_file($url, $local);
  }
  else {
    return $local;
  }
}

/**
 * Batch operation: download, unpack, and verify a project.
 *
 * This function assumes that the provided URL points to a file archive of
 * some sort. The URL can have any scheme that we have a file stream wrapper
 * to support. The file is downloaded to a local cache.
 *
 * @param string $project
 *   The short name of the project to download.
 * @param string $url
 *   The URL to download a specific project release archive file.
 * @param array &$context
 *   Reference to an array used for BatchAPI storage.
 *
 * @see update_manager_download_page()
 */
function update_manager_batch_project_get($project, $url, &$context) {
  // This is here to show the user that we are in the process of downloading.
  if (!isset($context['sandbox']['started'])) {
    $context['sandbox']['started'] = TRUE;
    $context['message'] = t('Downloading %project', array('%project' => $project));
    $context['finished'] = 0;
    return;
  }

  // Actually try to download the file.
  if (!($local_cache = update_manager_file_get($url))) {
    $context['results']['errors'][$project] = t('Failed to download %project from %url', array('%project' => $project, '%url' => $url));
    return;
  }

  // Extract it.
  $extract_directory = _update_manager_extract_directory();
  try {
    update_manager_archive_extract($local_cache, $extract_directory);
  }
  catch (Exception $e) {
    $context['results']['errors'][$project] = $e->getMessage();
    return;
  }

  // Verify it.
  try {
    update_manager_archive_verify($project, $local_cache, $extract_directory);
  }
  catch (Exception $e) {
    $context['results']['errors'][$project] = $e->getMessage();
    return;
  }

  // Yay, success.
  $context['results']['projects'][$project] = $url;
  $context['finished'] = 1;
}

/**
 * @} End of "defgroup update_manager_file".
 */
