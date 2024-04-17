<?php

/**
 * @file
 * Code required only when comparing available updates to existing data.
 */

use Drupal\Core\Extension\ExtensionVersion;
use Drupal\Core\Utility\Error;
use Drupal\update\ProjectRelease;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\update\ProjectCoreCompatibility;

/**
 * Determines version and type information for currently installed projects.
 *
 * Processes the list of projects on the system to figure out the currently
 * installed versions, and other information that is required before we can
 * compare against the available releases to produce the status report.
 *
 * @param $projects
 *   Array of project information from
 *   \Drupal\update\UpdateManager::getProjects().
 */
function update_process_project_info(&$projects) {
  foreach ($projects as $key => $project) {
    // Assume an official release until we see otherwise.
    $install_type = 'official';

    $info = $project['info'];

    if (isset($info['version'])) {
      // Check for development snapshots
      if (preg_match('@(dev|HEAD)@', $info['version'])) {
        $install_type = 'dev';
      }

      // Figure out what the currently installed major version is. We need
      // to handle both contribution (e.g. "5.x-1.3", major = 1) and core
      // (e.g. "5.1", major = 5) version strings.
      $matches = [];
      if (preg_match('/^(\d+\.x-)?(\d+)\..*$/', $info['version'], $matches)) {
        $info['major'] = $matches[2];
      }
      else {
        // This would only happen for version strings that don't follow the
        // drupal.org convention.
        $info['major'] = -1;
      }
    }
    else {
      // No version info available at all.
      $install_type = 'unknown';
      $info['version'] = t('Unknown');
      $info['major'] = -1;
    }

    // Finally, save the results we care about into the $projects array.
    $projects[$key]['existing_version'] = $info['version'];
    $projects[$key]['existing_major'] = $info['major'];
    $projects[$key]['install_type'] = $install_type;
  }
}

/**
 * Calculates the current update status of all projects on the site.
 *
 * The results of this function are expensive to compute, especially on sites
 * with lots of modules or themes, since it involves a lot of comparisons and
 * other operations. Therefore, we store the results. However, since this is not
 * the data about available updates fetched from the network, it is ok to
 * invalidate it somewhat quickly. If we keep this data for very long, site
 * administrators are more likely to see incorrect results if they upgrade to a
 * newer version of a module or theme but do not visit certain pages that
 * automatically clear this.
 *
 * @param array $available
 *   Data about available project releases.
 *
 * @return array
 *   An array of installed projects with current update status information.
 *
 * @see update_get_available()
 * @see \Drupal\update\UpdateManager::getProjects()
 * @see update_process_project_info()
 * @see \Drupal\update\UpdateManagerInterface::projectStorage()
 * @see \Drupal\update\ProjectCoreCompatibility::setReleaseMessage()
 */
function update_calculate_project_data($available) {
  // Retrieve the projects from storage, if present.
  $projects = \Drupal::service('update.manager')->projectStorage('update_project_data');
  // If $projects is empty, then the data must be rebuilt.
  // Otherwise, return the data and skip the rest of the function.
  if (!empty($projects)) {
    return $projects;
  }
  $projects = \Drupal::service('update.manager')->getProjects();
  update_process_project_info($projects);
  if (isset($projects['drupal']) && !empty($available['drupal'])) {
    // Calculate core status first so that it is complete before
    // \Drupal\update\ProjectCoreCompatibility::setReleaseMessage() is called
    // for each module below.
    update_calculate_project_update_status($projects['drupal'], $available['drupal']);
    if (isset($available['drupal']['releases']) && !empty($available['drupal']['supported_branches'])) {
      $supported_branches = explode(',', $available['drupal']['supported_branches']);
      $project_core_compatibility = new ProjectCoreCompatibility($projects['drupal'], $available['drupal']['releases'], $supported_branches);
    }
  }

  foreach ($projects as $project => $project_info) {
    if (isset($available[$project])) {
      if ($project === 'drupal') {
        continue;
      }
      update_calculate_project_update_status($projects[$project], $available[$project]);
      // Inject the list of compatible core versions to show administrator(s)
      // which versions of core a given available update can be installed with.
      // Since individual releases of a project can be compatible with different
      // versions of core, and even multiple major versions of core (for
      // example, 8.9.x and 9.0.x), this list will hopefully help
      // administrator(s) know which available updates they can upgrade a given
      // project to.
      if (isset($project_core_compatibility)) {
        $project_core_compatibility->setReleaseMessage($projects[$project]);
      }
    }
    else {
      $projects[$project]['status'] = UpdateFetcherInterface::UNKNOWN;
      $projects[$project]['reason'] = t('No available releases found');
    }
  }
  // Give other modules a chance to alter the status (for example, to allow a
  // contrib module to provide fine-grained settings to ignore specific
  // projects or releases).
  \Drupal::moduleHandler()->alter('update_status', $projects);

  // Store the site's update status for at most 1 hour.
  \Drupal::keyValueExpirable('update')->setWithExpire('update_project_data', $projects, 3600);
  return $projects;
}

/**
 * Calculates the current update status of a specific project.
 *
 * This function is the heart of the update status feature. For each project it
 * is invoked with, it first checks if the project has been flagged with a
 * special status like "unsupported" or "insecure", or if the project node
 * itself has been unpublished. In any of those cases, the project is marked
 * with an error and the next project is considered.
 *
 * If the project itself is valid, the function decides what major release
 * series to consider. The project defines its currently supported branches in
 * its Drupal.org for the project, so the first step is to make sure the
 * development branch of the current version is still supported. If so, then the
 * major version of the current version is used. If the current version is not
 * in a supported branch, the next supported branch is used to determine the
 * major version to use. There's also a check to make sure that this function
 * never recommends an earlier release than the currently installed major
 * version.
 *
 * Given a target major version, the available releases are scanned looking for
 * the specific release to recommend (avoiding beta releases and development
 * snapshots if possible). For the target major version, the highest patch level
 * is found. If there is a release at that patch level with no extra ("beta",
 * etc.), then the release at that patch level with the most recent release date
 * is recommended. If every release at that patch level has extra (only betas),
 * then the latest release from the previous patch level is recommended. For
 * example:
 *
 * - 1.6-bugfix <-- recommended version because 1.6 already exists.
 * - 1.6
 *
 * or
 *
 * - 1.6-beta
 * - 1.5 <-- recommended version because no 1.6 exists.
 * - 1.4
 *
 * Also, the latest release from the same major version is looked for, even beta
 * releases, to display to the user as the "Latest version" option.
 * Additionally, the latest official release from any higher major versions that
 * have been released is searched for to provide a set of "Also available"
 * options.
 *
 * Finally, and most importantly, the release history continues to be scanned
 * until the currently installed release is reached, searching for anything
 * marked as a security update. If any security updates have been found between
 * the recommended release and the installed version, all of the releases that
 * included a security fix are recorded so that the site administrator can be
 * warned their site is insecure, and links pointing to the release notes for
 * each security update can be included (which, in turn, will link to the
 * official security announcements for each vulnerability).
 *
 * This function relies on the fact that the .xml release history data comes
 * sorted based on major version and patch level, then finally by release date
 * if there are multiple releases such as betas from the same major.patch
 * version (e.g., 5.x-1.5-beta1, 5.x-1.5-beta2, and 5.x-1.5). Development
 * snapshots for a given major version are always listed last.
 *
 * NOTE: This function *must* set a value for $project_data['status'] before
 * returning, or the rest of the Update Manager will break in unexpected ways.
 *
 * @param $project_data
 *   An array containing information about a specific project.
 * @param $available
 *   Data about available project releases of a specific project.
 */
function update_calculate_project_update_status(&$project_data, $available) {
  foreach (['title', 'link'] as $attribute) {
    if (!isset($project_data[$attribute]) && isset($available[$attribute])) {
      $project_data[$attribute] = $available[$attribute];
    }
  }

  // If the project status is marked as something bad, there's nothing else
  // to consider.
  if (isset($available['project_status'])) {
    switch ($available['project_status']) {
      case 'insecure':
        $project_data['status'] = UpdateManagerInterface::NOT_SECURE;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = [];
        }
        $project_data['extra'][] = [
          'label' => t('Project not secure'),
          'data' => t('This project has been labeled insecure by the Drupal security team, and is no longer available for download. Immediately uninstalling everything included by this project is strongly recommended!'),
        ];
        break;

      case 'unpublished':
      case 'revoked':
        $project_data['status'] = UpdateManagerInterface::REVOKED;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = [];
        }
        $project_data['extra'][] = [
          'label' => t('Project revoked'),
          'data' => t('This project has been revoked, and is no longer available for download. Uninstalling everything included by this project is strongly recommended!'),
        ];
        break;

      case 'unsupported':
        $project_data['status'] = UpdateManagerInterface::NOT_SUPPORTED;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = [];
        }
        $project_data['extra'][] = [
          'label' => t('Project not supported'),
          'data' => t('This project is no longer supported, and is no longer available for download. Uninstalling everything included by this project is strongly recommended!'),
        ];
        break;

      case 'not-fetched':
        $project_data['status'] = UpdateFetcherInterface::NOT_FETCHED;
        $project_data['reason'] = t('Failed to get available update data.');
        break;

      default:
        // Assume anything else (e.g. 'published') is valid and we should
        // perform the rest of the logic in this function.
        break;
    }
  }

  if (!empty($project_data['status'])) {
    // We already know the status for this project, so there's nothing else to
    // compute. Record the project status into $project_data and we're done.
    $project_data['project_status'] = $available['project_status'];
    return;
  }

  // Figure out the target major version.
  // Off Drupal.org, '0' could be a valid version string, so don't use empty().
  if (!isset($project_data['existing_version']) || $project_data['existing_version'] === '') {
    $project_data['status'] = UpdateFetcherInterface::UNKNOWN;
    $project_data['reason'] = t('Empty version');
    return;
  }
  try {
    $existing_major = ExtensionVersion::createFromVersionString($project_data['existing_version'])->getMajorVersion();
  }
  catch (UnexpectedValueException $exception) {
    // If the version has an unexpected value we can't determine updates.
    $project_data['status'] = UpdateFetcherInterface::UNKNOWN;
    $project_data['reason'] = t('Invalid version: @existing_version', ['@existing_version' => $project_data['existing_version']]);
    return;
  }
  $supported_branches = [];
  if (isset($available['supported_branches'])) {
    $supported_branches = explode(',', $available['supported_branches']);
  }

  $is_in_supported_branch = function ($version) use ($supported_branches) {
    foreach ($supported_branches as $supported_branch) {
      if (str_starts_with($version, $supported_branch)) {
        return TRUE;
      }
    }
    return FALSE;
  };
  if ($is_in_supported_branch($project_data['existing_version'])) {
    // Still supported, stay at the current major version.
    $target_major = $existing_major;
  }
  elseif ($supported_branches) {
    // We know the current release is unsupported since it is not in
    // 'supported_branches' list. We should use the next valid supported
    // branch for the target major version.
    $project_data['status'] = UpdateManagerInterface::NOT_SUPPORTED;
    foreach ($supported_branches as $supported_branch) {
      try {
        $target_major = ExtensionVersion::createFromSupportBranch($supported_branch)->getMajorVersion();
        break;
      }
      catch (UnexpectedValueException $exception) {
        continue;
      }
    }
    if (!isset($target_major)) {
      // If there are no valid support branches, use the current major.
      $target_major = $existing_major;
    }

  }
  else {
    // Malformed XML file? Stick with the current branch.
    $target_major = $existing_major;
  }

  // Make sure we never tell the admin to downgrade. If we recommended an
  // earlier version than the one they're running, they'd face an
  // impossible data migration problem, since Drupal never supports a DB
  // downgrade path. In the unfortunate case that what they're running is
  // unsupported, and there's nothing newer for them to upgrade to, we
  // can't print out a "Recommended version", but just have to tell them
  // what they have is unsupported and let them figure it out.
  $target_major = max($existing_major, $target_major);

  // If the project is marked as UpdateFetcherInterface::FETCH_PENDING, it
  // means that the data we currently have (if any) is stale, and we've got a
  // task queued up to (re)fetch the data. In that case, we mark it as such,
  // merge in whatever data we have (e.g. project title and link), and move on.
  if (!empty($available['fetch_status']) && $available['fetch_status'] == UpdateFetcherInterface::FETCH_PENDING) {
    $project_data['status'] = UpdateFetcherInterface::FETCH_PENDING;
    $project_data['reason'] = t('No available update data');
    $project_data['fetch_status'] = $available['fetch_status'];
    return;
  }

  // Defend ourselves from XML history files that contain no releases.
  if (empty($available['releases'])) {
    $project_data['status'] = UpdateFetcherInterface::UNKNOWN;
    $project_data['reason'] = t('No available releases found');
    return;
  }

  $recommended_version_without_extra = '';
  $recommended_release = NULL;
  $release_is_supported = FALSE;
  foreach ($available['releases'] as $version => $release_info) {
    try {
      $release = ProjectRelease::createFromArray($release_info);
    }
    catch (UnexpectedValueException $exception) {
      // Ignore releases that are in an invalid format. Although this is highly
      // unlikely we should still process releases in the correct format.
      Error::logException(\Drupal::logger('update'), $exception, 'Invalid project format: @release', ['@release' => print_r($release_info, TRUE)]);
      continue;
    }

    try {
      $release_module_version = ExtensionVersion::createFromVersionString($release->getVersion());
    }
    catch (UnexpectedValueException $exception) {
      continue;
    }
    // This release is supported only if it is in a supported branch and is
    // not unsupported.
    $release_is_supported = $is_in_supported_branch($release->getVersion()) && !$release->isUnsupported();
    // First, if this is the existing release, check a few conditions.
    if ($project_data['existing_version'] === $version) {
      if ($release->isInsecure()) {
        $project_data['status'] = UpdateManagerInterface::NOT_SECURE;
      }
      elseif (!$release->isPublished()) {
        $project_data['status'] = UpdateManagerInterface::REVOKED;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = [];
        }
        $project_data['extra'][] = [
          'class' => ['release-revoked'],
          'label' => t('Release revoked'),
          'data' => t('Your currently installed release has been revoked, and is no longer available for download. Uninstalling everything included in this release or upgrading is strongly recommended!'),
        ];
      }
      elseif (!$release_is_supported) {
        $project_data['status'] = UpdateManagerInterface::NOT_SUPPORTED;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = [];
        }
        if (empty($project_data['recommended']) && empty($project_data['also'])) {
          $unsupported_message = t('Your currently installed release is now unsupported, is no longer available for download and no update is available. Uninstalling everything included in this release is strongly recommended!');
        }
        else {
          $unsupported_message = t('Your currently installed release is now unsupported, and is no longer available for download. Uninstalling everything included in this release or upgrading is strongly recommended!');
        }
        $project_data['extra'][] = [
          'class' => ['release-not-supported'],
          'label' => t('Release not supported'),
          'data' => $unsupported_message,
        ];
      }
    }
    // Other than the currently installed release, ignore unpublished, insecure,
    // or unsupported updates.
    elseif (!$release->isPublished() ||
            !$release_is_supported ||
            $release->isInsecure()
    ) {
      continue;
    }
    // Ignore dev releases with no date. These are either broken releases or
    // stub releases to allow them to be selected on drupal.org project issues.
    elseif ($release->getDate() === NULL && $release_module_version->getVersionExtra() === 'dev') {
      continue;
    }

    $release_major_version = $release_module_version->getMajorVersion();
    // See if this is a higher major version than our target and yet still
    // supported. If so, record it as an "Also available" release.
    if ($release_major_version > $target_major) {
      if (!isset($project_data['also'])) {
        $project_data['also'] = [];
      }
      if (!isset($project_data['also'][$release_major_version])) {
        $project_data['also'][$release_major_version] = $version;
        $project_data['releases'][$version] = $release_info;
      }
      // Otherwise, this release can't matter to us, since it's neither
      // from the release series we're currently using nor the recommended
      // release. We don't even care about security updates for this
      // branch, since if a project maintainer puts out a security release
      // at a higher major version and not at the lower major version,
      // they must remove the lower version from the supported major
      // versions at the same time, in which case we won't hit this code.
      continue;
    }

    // Look for the 'latest version' if we haven't found it yet. Latest is
    // defined as the most recent version for the target major version.
    if (!isset($project_data['latest_version'])
        && $release_major_version == $target_major) {
      $project_data['latest_version'] = $version;
      $project_data['releases'][$version] = $release_info;
    }

    // Look for the development snapshot release for this branch.
    if (!isset($project_data['dev_version'])
        && $release_major_version == $target_major
        && $release_module_version->getVersionExtra() === 'dev') {
      $project_data['dev_version'] = $version;
      $project_data['releases'][$version] = $release_info;
    }

    if ($release_module_version->getVersionExtra()) {
      $release_version_without_extra = str_replace('-' . $release_module_version->getVersionExtra(), '', $release->getVersion());
    }
    else {
      $release_version_without_extra = $release->getVersion();
    }

    // Look for the 'recommended' version if we haven't found it yet (see
    // PHPDoc at the top of this function for the definition).
    if (!isset($project_data['recommended'])
        && $release_major_version == $target_major && $release_is_supported) {
      if ($recommended_version_without_extra !== $release_version_without_extra) {
        $recommended_version_without_extra = $release_version_without_extra;
        $recommended_release = $release_info;
      }
      if ($release_module_version->getVersionExtra() === NULL) {
        $project_data['recommended'] = $recommended_release['version'];
        $project_data['releases'][$recommended_release['version']] = $recommended_release;
      }
    }

    // Stop searching once we hit the currently installed version.
    if ($project_data['existing_version'] === $version) {
      break;
    }

    // If we're running a dev snapshot and have a timestamp, stop
    // searching for security updates once we hit an official release
    // older than what we've got. Allow 100 seconds of leeway to handle
    // differences between the datestamp in the .info.yml file and the
    // timestamp of the tarball itself (which are usually off by 1 or 2
    // seconds) so that we don't flag that as a new release.
    if ($project_data['install_type'] == 'dev') {
      if (empty($project_data['datestamp'])) {
        // We don't have current timestamp info, so we can't know.
        continue;
      }
      elseif ($release->getDate() && $project_data['datestamp'] + 100 > $release->getDate()) {
        // We're newer than this, so we can skip it.
        continue;
      }
    }

    if ($release->isSecurityRelease()) {
      $project_data['security updates'][] = $release_info;
    }
  }

  // If we were unable to find a recommended version, then make the latest
  // version the recommended version if possible.
  if (!isset($project_data['recommended']) && isset($project_data['latest_version']) && $release_is_supported) {
    $project_data['recommended'] = $project_data['latest_version'];
  }

  if (isset($project_data['status'])) {
    // If we already know the status, we're done.
    return;
  }

  // If we don't know what to recommend, there's nothing we can report.
  // Bail out early.
  if (!isset($project_data['recommended'])) {
    $project_data['status'] = UpdateFetcherInterface::UNKNOWN;
    $project_data['reason'] = t('No available releases found');
    return;
  }

  // If we're running a dev snapshot, compare the date of the dev snapshot
  // with the latest official version, and record the absolute latest in
  // 'latest_dev' so we can correctly decide if there's a newer release
  // than our current snapshot.
  if ($project_data['install_type'] == 'dev') {
    if (isset($project_data['dev_version']) && $available['releases'][$project_data['dev_version']]['date'] > $available['releases'][$project_data['latest_version']]['date']) {
      $project_data['latest_dev'] = $project_data['dev_version'];
    }
    else {
      $project_data['latest_dev'] = $project_data['latest_version'];
    }
  }

  // Figure out the status, based on what we've seen and the install type.
  switch ($project_data['install_type']) {
    case 'official':
      if ($project_data['existing_version'] === $project_data['recommended'] || $project_data['existing_version'] === $project_data['latest_version']) {
        $project_data['status'] = UpdateManagerInterface::CURRENT;
      }
      else {
        $project_data['status'] = UpdateManagerInterface::NOT_CURRENT;
      }
      break;

    case 'dev':
      $latest = $available['releases'][$project_data['latest_dev']];
      if (empty($project_data['datestamp'])) {
        $project_data['status'] = UpdateFetcherInterface::NOT_CHECKED;
        $project_data['reason'] = t('Unknown release date');
      }
      elseif (($project_data['datestamp'] + 100 > $latest['date'])) {
        $project_data['status'] = UpdateManagerInterface::CURRENT;
      }
      else {
        $project_data['status'] = UpdateManagerInterface::NOT_CURRENT;
      }
      break;

    default:
      $project_data['status'] = UpdateFetcherInterface::UNKNOWN;
      $project_data['reason'] = t('Invalid info');
  }
}
