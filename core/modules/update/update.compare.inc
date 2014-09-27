<?php

/**
 * @file
 * Code required only when comparing available updates to existing data.
 */

/**
 * Fetches an array of installed and enabled projects.
 *
 * This is only responsible for generating an array of projects (taking into
 * account projects that include more than one module or theme). Other
 * information like the specific version and install type (official release,
 * dev snapshot, etc) is handled later in update_process_project_info() since
 * that logic is only required when preparing the status report, not for
 * fetching the available release data.
 *
 * This array is fairly expensive to construct, since it involves a lot of disk
 * I/O, so we store the results. However, since this is not the data about
 * available updates fetched from the network, it is acceptable to invalidate it
 * somewhat quickly. If we keep this data for very long, site administrators are
 * more likely to see incorrect results if they upgrade to a newer version of a
 * module or theme but do not visit certain pages that automatically clear this
 * data.
 *
 * @return
 *   An associative array of currently enabled projects keyed by the
 *   machine-readable project short name. Each project contains:
 *   - name: The machine-readable project short name.
 *   - info: An array with values from the main .info.yml file for this project.
 *     - name: The human-readable name of the project.
 *     - package: The package that the project is grouped under.
 *     - version: The version of the project.
 *     - project: The Drupal.org project name.
 *     - datestamp: The date stamp of the project's main .info.yml file.
 *     - _info_file_ctime: The maximum file change time for all of the .info.yml
 *       files included in this project.
 *   - datestamp: The date stamp when the project was released, if known.
 *   - includes: An associative array containing all projects included with this
 *     project, keyed by the machine-readable short name with the human-readable
 *     name as value.
 *   - project_type: The type of project. Allowed values are 'module' and
 *     'theme'.
 *   - project_status: This indicates if the project is enabled and will always
 *     be TRUE, as the function only returns enabled projects.
 *   - sub_themes: If the project is a theme it contains an associative array of
 *     all sub-themes.
 *   - base_themes: If the project is a theme it contains an associative array
 *     of all base-themes.
 *
 * @see \Drupal\update\UpdateManager::getProjects()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('update.manager')->getProjects().
 */
function update_get_projects() {
  return \Drupal::service('update.manager')->getProjects();
}

/**
 * Determines version and type information for currently installed projects.
 *
 * Processes the list of projects on the system to figure out the currently
 * installed versions, and other information that is required before we can
 * compare against the available releases to produce the status report.
 *
 * @param $projects
 *   Array of project information from update_get_projects().
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
      $matches = array();
      if (preg_match('/^(\d+\.x-)?(\d+)\..*$/', $info['version'], $matches)) {
        $info['major'] = $matches[2];
      }
      elseif (!isset($info['major'])) {
        // This would only happen for version strings that don't follow the
        // drupal.org convention. We let contribs define "major" in their
        // .info.yml in this case, and only if that's missing would we hit this.
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
 * @return
 *   An array of installed projects with current update status information.
 *
 * @see update_get_available()
 * @see update_get_projects()
 * @see update_process_project_info()
 * @see update_project_storage()
 */
function update_calculate_project_data($available) {
  // Retrieve the projects from storage, if present.
  $projects = update_project_storage('update_project_data');
  // If $projects is empty, then the data must be rebuilt.
  // Otherwise, return the data and skip the rest of the function.
  if (!empty($projects)) {
    return $projects;
  }
  $projects = update_get_projects();
  update_process_project_info($projects);
  foreach ($projects as $project => $project_info) {
    if (isset($available[$project])) {
      update_calculate_project_update_status($projects[$project], $available[$project]);
    }
    else {
      $projects[$project]['status'] = UPDATE_UNKNOWN;
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
 * series to consider. The project defines what the currently supported major
 * versions are for each version of core, so the first step is to make sure the
 * current version is still supported. If so, that's the target version. If the
 * current version is unsupported, the project maintainer's recommended major
 * version is used. There's also a check to make sure that this function never
 * recommends an earlier release than the currently installed major version.
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
 * @param $project_data
 *   An array containing information about a specific project.
 * @param $available
 *   Data about available project releases of a specific project.
 */
function update_calculate_project_update_status(&$project_data, $available) {
  foreach (array('title', 'link') as $attribute) {
    if (!isset($project_data[$attribute]) && isset($available[$attribute])) {
      $project_data[$attribute] = $available[$attribute];
    }
  }

  // If the project status is marked as something bad, there's nothing else
  // to consider.
  if (isset($available['project_status'])) {
    switch ($available['project_status']) {
      case 'insecure':
        $project_data['status'] = UPDATE_NOT_SECURE;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = array();
        }
        $project_data['extra'][] = array(
          'label' => t('Project not secure'),
          'data' => t('This project has been labeled insecure by the Drupal security team, and is no longer available for download. Immediately disabling everything included by this project is strongly recommended!'),
        );
        break;
      case 'unpublished':
      case 'revoked':
        $project_data['status'] = UPDATE_REVOKED;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = array();
        }
        $project_data['extra'][] = array(
          'label' => t('Project revoked'),
          'data' => t('This project has been revoked, and is no longer available for download. Disabling everything included by this project is strongly recommended!'),
        );
        break;
      case 'unsupported':
        $project_data['status'] = UPDATE_NOT_SUPPORTED;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = array();
        }
        $project_data['extra'][] = array(
          'label' => t('Project not supported'),
          'data' => t('This project is no longer supported, and is no longer available for download. Disabling everything included by this project is strongly recommended!'),
        );
        break;
      case 'not-fetched':
        $project_data['status'] = UPDATE_NOT_FETCHED;
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
  $existing_major = $project_data['existing_major'];
  $supported_majors = array();
  if (isset($available['supported_majors'])) {
    $supported_majors = explode(',', $available['supported_majors']);
  }
  elseif (isset($available['default_major'])) {
    // Older release history XML file without supported or recommended.
    $supported_majors[] = $available['default_major'];
  }

  if (in_array($existing_major, $supported_majors)) {
    // Still supported, stay at the current major version.
    $target_major = $existing_major;
  }
  elseif (isset($available['recommended_major'])) {
    // Since 'recommended_major' is defined, we know this is the new XML
    // format. Therefore, we know the current release is unsupported since
    // its major version was not in the 'supported_majors' list. We should
    // find the best release from the recommended major version.
    $target_major = $available['recommended_major'];
    $project_data['status'] = UPDATE_NOT_SUPPORTED;
  }
  elseif (isset($available['default_major'])) {
    // Older release history XML file without recommended, so recommend
    // the currently defined "default_major" version.
    $target_major = $available['default_major'];
  }
  else {
    // Malformed XML file? Stick with the current version.
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

  $release_patch_changed = '';
  $patch = '';

  // If the project is marked as UPDATE_FETCH_PENDING, it means that the
  // data we currently have (if any) is stale, and we've got a task queued
  // up to (re)fetch the data. In that case, we mark it as such, merge in
  // whatever data we have (e.g. project title and link), and move on.
  if (!empty($available['fetch_status']) && $available['fetch_status'] == UPDATE_FETCH_PENDING) {
    $project_data['status'] = UPDATE_FETCH_PENDING;
    $project_data['reason'] = t('No available update data');
    $project_data['fetch_status'] = $available['fetch_status'];
    return;
  }

  // Defend ourselves from XML history files that contain no releases.
  if (empty($available['releases'])) {
    $project_data['status'] = UPDATE_UNKNOWN;
    $project_data['reason'] = t('No available releases found');
    return;
  }
  foreach ($available['releases'] as $version => $release) {
    // First, if this is the existing release, check a few conditions.
    if ($project_data['existing_version'] === $version) {
      if (isset($release['terms']['Release type']) &&
          in_array('Insecure', $release['terms']['Release type'])) {
        $project_data['status'] = UPDATE_NOT_SECURE;
      }
      elseif ($release['status'] == 'unpublished') {
        $project_data['status'] = UPDATE_REVOKED;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = array();
        }
        $project_data['extra'][] = array(
          'class' => array('release-revoked'),
          'label' => t('Release revoked'),
          'data' => t('Your currently installed release has been revoked, and is no longer available for download. Disabling everything included in this release or upgrading is strongly recommended!'),
        );
      }
      elseif (isset($release['terms']['Release type']) &&
              in_array('Unsupported', $release['terms']['Release type'])) {
        $project_data['status'] = UPDATE_NOT_SUPPORTED;
        if (empty($project_data['extra'])) {
          $project_data['extra'] = array();
        }
        $project_data['extra'][] = array(
          'class' => array('release-not-supported'),
          'label' => t('Release not supported'),
          'data' => t('Your currently installed release is now unsupported, and is no longer available for download. Disabling everything included in this release or upgrading is strongly recommended!'),
        );
      }
    }

    // Otherwise, ignore unpublished, insecure, or unsupported releases.
    if ($release['status'] == 'unpublished' ||
        (isset($release['terms']['Release type']) &&
         (in_array('Insecure', $release['terms']['Release type']) ||
          in_array('Unsupported', $release['terms']['Release type'])))) {
      continue;
    }

    // See if this is a higher major version than our target and yet still
    // supported. If so, record it as an "Also available" release.
    // Note: Some projects have a HEAD release from CVS days, which could
    // be one of those being compared. They would not have version_major
    // set, so we must call isset first.
    if (isset($release['version_major']) && $release['version_major'] > $target_major) {
      if (in_array($release['version_major'], $supported_majors)) {
        if (!isset($project_data['also'])) {
          $project_data['also'] = array();
        }
        if (!isset($project_data['also'][$release['version_major']])) {
          $project_data['also'][$release['version_major']] = $version;
          $project_data['releases'][$version] = $release;
        }
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
        && $release['version_major'] == $target_major) {
      $project_data['latest_version'] = $version;
      $project_data['releases'][$version] = $release;
    }

    // Look for the development snapshot release for this branch.
    if (!isset($project_data['dev_version'])
        && $release['version_major'] == $target_major
        && isset($release['version_extra'])
        && $release['version_extra'] == 'dev') {
      $project_data['dev_version'] = $version;
      $project_data['releases'][$version] = $release;
    }

    // Look for the 'recommended' version if we haven't found it yet (see
    // phpdoc at the top of this function for the definition).
    if (!isset($project_data['recommended'])
        && $release['version_major'] == $target_major
        && isset($release['version_patch'])) {
      if ($patch != $release['version_patch']) {
        $patch = $release['version_patch'];
        $release_patch_changed = $release;
      }
      if (empty($release['version_extra']) && $patch == $release['version_patch']) {
        $project_data['recommended'] = $release_patch_changed['version'];
        $project_data['releases'][$release_patch_changed['version']] = $release_patch_changed;
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
      elseif (isset($release['date']) && ($project_data['datestamp'] + 100 > $release['date'])) {
        // We're newer than this, so we can skip it.
        continue;
      }
    }

    // See if this release is a security update.
    if (isset($release['terms']['Release type'])
        && in_array('Security update', $release['terms']['Release type'])) {
      $project_data['security updates'][] = $release;
    }
  }

  // If we were unable to find a recommended version, then make the latest
  // version the recommended version if possible.
  if (!isset($project_data['recommended']) && isset($project_data['latest_version'])) {
    $project_data['recommended'] = $project_data['latest_version'];
  }

  //
  // Check to see if we need an update or not.
  //

  if (!empty($project_data['security updates'])) {
    // If we found security updates, that always trumps any other status.
    $project_data['status'] = UPDATE_NOT_SECURE;
  }

  if (isset($project_data['status'])) {
    // If we already know the status, we're done.
    return;
  }

  // If we don't know what to recommend, there's nothing we can report.
  // Bail out early.
  if (!isset($project_data['recommended'])) {
    $project_data['status'] = UPDATE_UNKNOWN;
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
        $project_data['status'] = UPDATE_CURRENT;
      }
      else {
        $project_data['status'] = UPDATE_NOT_CURRENT;
      }
      break;

    case 'dev':
      $latest = $available['releases'][$project_data['latest_dev']];
      if (empty($project_data['datestamp'])) {
        $project_data['status'] = UPDATE_NOT_CHECKED;
        $project_data['reason'] = t('Unknown release date');
      }
      elseif (($project_data['datestamp'] + 100 > $latest['date'])) {
        $project_data['status'] = UPDATE_CURRENT;
      }
      else {
        $project_data['status'] = UPDATE_NOT_CURRENT;
      }
      break;

    default:
      $project_data['status'] = UPDATE_UNKNOWN;
      $project_data['reason'] = t('Invalid info');
  }
}

/**
 * Retrieves update storage data or empties it.
 *
 * Two very expensive arrays computed by this module are the list of all
 * installed modules and themes (and .info.yml data, project associations, etc), and
 * the current status of the site relative to the currently available releases.
 * These two arrays are stored and used whenever possible. The data is cleared
 * whenever the administrator visits the status report, available updates
 * report, or the module or theme administration pages, since we should always
 * recompute the most current values on any of those pages.
 *
 * Note: while both of these arrays are expensive to compute (in terms of disk
 * I/O and some fairly heavy CPU processing), neither of these is the actual
 * data about available updates that we have to fetch over the network from
 * updates.drupal.org. That information is stored in the
 * 'update_available_releases' collection -- it needs to persist longer than 1
 * hour and never get invalidated just by visiting a page on the site.
 *
 * @param $key
 *   The key of data to return. Valid options are 'update_project_data' and
 *   'update_project_projects'.
 *
 * @return
 *   The stored value of the $projects array generated by
 *   update_calculate_project_data() or update_get_projects(), or an empty array
 *   when the storage is cleared.
 *
 * @see \Drupal\update\UpdateManager::projectStorage()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('update.manager')->projectStorage().
 */
function update_project_storage($key) {
  return \Drupal::service('update.manager')->projectStorage($key);
}
