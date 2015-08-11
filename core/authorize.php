<?php

/**
 * @file
 * Administrative script for running authorized file operations.
 *
 * Using this script, the site owner (the user actually owning the files on the
 * webserver) can authorize certain file-related operations to proceed with
 * elevated privileges, for example to deploy and upgrade modules or themes.
 * Users should not visit this page directly, but instead use an administrative
 * user interface which knows how to redirect the user to this script as part of
 * a multistep process. This script actually performs the selected operations
 * without loading all of Drupal, to be able to more gracefully recover from
 * errors. Access to the script is controlled by a global killswitch in
 * settings.php ('allow_authorize_operations') and via the 'administer software
 * updates' permission.
 *
 * There are helper functions for setting up an operation to run via this
 * system in modules/system/system.module. For more information, see:
 * @link authorize Authorized operation helper functions @endlink
 */

use Drupal\Core\DrupalKernel;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Site\Settings;

// Change the directory to the Drupal root.
chdir('..');

$autoloader = require_once 'autoload.php';

/**
 * Global flag to identify update.php and authorize.php runs.
 *
 * Identifies update.php and authorize.php runs, avoiding unwanted operations
 * such as css/js preprocessing and translation, and solves some theming issues.
 * The flag is checked in other places in Drupal code (not just authorize.php).
 */
const MAINTENANCE_MODE = 'update';

/**
 * Determines if the current user is allowed to run authorize.php.
 *
 * The killswitch in settings.php overrides all else, otherwise, the user must
 * have access to the 'administer software updates' permission.
 *
 * @param \Symfony\Component\HttpFoundation\Request $request
 *  The incoming request.
 *
 * @return bool
 *   TRUE if the current user can run authorize.php, and FALSE if not.
 */
function authorize_access_allowed(Request $request) {
  $account = \Drupal::service('authentication')->authenticate($request);
  if ($account) {
    \Drupal::currentUser()->setAccount($account);
  }
  return Settings::get('allow_authorize_operations', TRUE) && \Drupal::currentUser()->hasPermission('administer software updates');
}

try {
  $request = Request::createFromGlobals();
  $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
  $kernel->prepareLegacyRequest($request);
}
catch (HttpExceptionInterface $e) {
  $response = new Response('', $e->getStatusCode());
  $response->prepare($request)->send();
  exit;
}

// We have to enable the user and system modules, even to check access and
// display errors via the maintenance theme.
\Drupal::moduleHandler()->addModule('system', 'core/modules/system');
\Drupal::moduleHandler()->addModule('user', 'core/modules/user');
\Drupal::moduleHandler()->load('system');
\Drupal::moduleHandler()->load('user');

// Initialize the maintenance theme for this administrative script.
drupal_maintenance_theme();

$content = [];
$show_messages = TRUE;

$is_allowed = authorize_access_allowed($request);

// Build content.
if ($is_allowed) {
  // Load both the Form API and Batch API.
  require_once __DIR__ . '/includes/form.inc';
  require_once __DIR__ . '/includes/batch.inc';

  if (isset($_SESSION['authorize_page_title'])) {
    $page_title = $_SESSION['authorize_page_title'];
  }
  else {
    $page_title = t('Authorize file system changes');
  }

  // See if we've run the operation and need to display a report.
  if (isset($_SESSION['authorize_results']) && $results = $_SESSION['authorize_results']) {

    // Clear the session out.
    unset($_SESSION['authorize_results']);
    unset($_SESSION['authorize_operation']);
    unset($_SESSION['authorize_filetransfer_info']);

    if (!empty($results['page_title'])) {
      $page_title = $results['page_title'];
    }
    if (!empty($results['page_message'])) {
      drupal_set_message($results['page_message']['message'], $results['page_message']['type']);
    }

    $content['authorize_report'] = array(
      '#theme' => 'authorize_report',
      '#messages' => $results['messages'],
    );

    if (is_array($results['tasks'])) {
      $links = $results['tasks'];
    }
    else {
      // Since this is being called outsite of the primary front controller,
      // the base_url needs to be set explicitly to ensure that links are
      // relative to the site root.
      // @todo Simplify with https://www.drupal.org/node/2548095
      $default_options = [
        '#type' => 'link',
        '#options' => [
          'absolute' => TRUE,
          'base_url' => $GLOBALS['base_url'],
        ],
      ];
      $links = [
        $default_options + [
          '#url' => Url::fromRoute('system.admin'),
          '#title' => t('Administration pages'),
        ],
        $default_options + [
          '#url' => Url::fromRoute('<front>'),
          '#title' => t('Front page'),
        ],
      ];
    }

    $content['next_steps'] = array(
      '#theme' => 'item_list',
      '#items' => $links,
      '#title' => t('Next steps'),
    );
  }
  // If a batch is running, let it run.
  elseif ($request->query->has('batch')) {
    $content = _batch_page($request);
    // If _batch_page() returns a response object (likely a JsonResponse for
    // JavaScript-based batch processing), send it immediately.
    if ($content instanceof Response) {
      $content->send();
      exit;
    }
  }
  else {
    if (empty($_SESSION['authorize_operation']) || empty($_SESSION['authorize_filetransfer_info'])) {
      $content = ['#markup' => t('It appears you have reached this page in error.')];
    }
    elseif (!$batch = batch_get()) {
      // We have a batch to process, show the filetransfer form.
      $content = \Drupal::formBuilder()->getForm('Drupal\Core\FileTransfer\Form\FileTransferAuthorizeForm');
    }
  }
  // We defer the display of messages until all operations are done.
  $show_messages = !(($batch = batch_get()) && isset($batch['running']));
}
else {
  \Drupal::logger('access denied')->warning('authorize.php');
  $page_title = t('Access denied');
  $content = ['#markup' => t('You are not allowed to access this page.')];
}

$bare_html_page_renderer = \Drupal::service('bare_html_page_renderer');
$response = $bare_html_page_renderer->renderBarePage($content, $page_title, 'maintenance_page', array(
  '#show_messages' => $show_messages,
));
if (!$is_allowed) {
  $response->setStatusCode(403);
}
$response->send();
