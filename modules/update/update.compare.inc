<?php
// $Id$

/**
 * @file
 * Code required only when comparing available updates to existing data.
 */

/**
 * Fetch an array of installed and enabled projects.
 *
 * This is only responsible for generating an array of projects (taking into
 * account projects that include more than one module or theme). Other
 * information like the specific version and install type (official release,
 * dev snapshot, etc) is handled later in update_process_project_info() since
 * that logic is only required when preparing the status report, not for
 * fetching the available release data.
 *
 * This array is fairly expensive to construct, since it involves a lot of
 * disk I/O, so we cache the results into the {cache_update} table using the
 * 'update_project_projects' cache ID. However, since this is not the data
 * about available updates fetched from the network, it is ok to invalidate it
 * somewhat quickly. If we keep this data for very long, site administrators
 * are more likely to see incorrect results if they upgrade to a newer version
 * of a module or theme but do not visit certain pages that automatically
 * clear this cache.
 *
 * @see update_process_project_info()
 * @see update_calculate_project_data()
 * @see update_project_cache()
 */
function update_get_projects() {
  static $projects = array();
  if (empty($projects)) {
    // Retrieve the projects from cache, if present.
    $projects = update_project_cache('update_project_projects');
    if (empty($projects)) {
      // Still empty, so we have to rebuild the cache.
      _update_process_info_list($projects, module_rebuild_cache(), 'module');
      _update_process_info_list($projects, system_theme_data(), 'theme');
      // Allow other modules to alter projects before fetching and comparing.
      drupal_alter('update_projects', $projects);
      // Cache the site's project data for at most 1 hour.
      _update_cache_set('update_project_projects', $projects, time() + 3600);
    }
  }
  return $projects;
}

/**
 * Populate an array of project data.
 */
function _update_process_info_list(&$projects, $list, $project_type) {
  foreach ($list as $file) {
    // A disabled base theme of an enabled sub-theme still has all of its code
    // run by the sub-theme, so we include it in our "enabled" projects list.
    if (!$file->status && !empty($file->sub_themes)) {
      foreach ($file->sub_themes as $key => $name) {
        // Build a list of enabled sub-themes.
        if ($list[$key]->status) {
          $file->enabled_sub_themes[$key] = $name;
        }
      }
      // If there are no enabled subthemes, we should ingore this theme and go
      // on to the next one.
      if (empty($file->enabled_sub_themes)) {
        continue;
      }
    }
    elseif (empty($file->status)) {
      // Skip disabled modules or themes.
      continue;
    }

    // Skip if the .info file is broken.
    if (empty($file->info)) {
      continue;
    }

    // If the .info doesn't define the 'project', try to figure it out.
    if (!isset($file->info['project'])) {
      $file->info['project'] = update_get_project_name($file);
    }

    // If we still don't know the 'project', give up.
    if (empty($file->info['project'])) {
      continue;
    }

    // If we don't already know it, grab the change time on the .info file
    // itself. Note: we need to use the ctime, not the mtime (modification
    // time) since many (all?) tar implementations will go out of their way to
    // set the mtime on the files it creates to the timestamps recorded in the
    // tarball. We want to see the last time the file was changed on disk,
    // which is left alone by tar and correctly set to the time the .info file
    // was unpacked.
    if (!isset($file->info['_info_file_ctime'])) {
      $info_filename = dirname($file->filename) .'/'. $file->name .'.info';
      $file->info['_info_file_ctime'] = filectime($info_filename);
    }

    if (!isset($file->info['datestamp'])) {
      $file->info['datestamp'] = 0;
    }

    $project_name = $file->info['project'];

    // Add a list of sub-themes that "depend on" the project and a list of base
    // themes that are "required by" the project.
    if ($project_name == 'drupal') {
      // Drupal core is always required, so this extra info would be noise.
      $sub_themes = array();
      $base_themes = array();
    }
    else {
      // Add list of enabled sub-themes.
      $sub_themes = !empty($file->enabled_sub_themes) ? $file->enabled_sub_themes : array();
      // Add list of base themes.
      $base_themes = !empty($file->base_themes) ? $file->base_themes : array();
    }

    if (!isset($projects[$project_name])) {
      // Only process this if we haven't done this project, since a single
      // project can have multiple modules or themes.
      $projects[$project_name] = array(
        'name' => $project_name,
        // Only save attributes from the .info file we care about so we do not
        // bloat our RAM usage needlessly.
        'info' => update_filter_project_info($file->info),
        'datestamp' => $file->info['datestamp'],
        'includes' => array($file->name => $file->info['name']),
        'project_type' => $project_name == 'drupal' ? 'core' : $project_type,
        'sub_themes' => $sub_themes,
        'base_themes' => $base_themes,
      );
    }
    else {
      $projects[$project_name]['includes'][$file->name] = $file->info['name'];
      $projects[$project_name]['info']['_info_file_ctime'] = max($projects[$project_name]['info']['_info_file_ctime'], $file->info['_info_file_ctime']);
      $projects[$project_name]['datestamp'] = max($projects[$project_name]['datestamp'], $file->info['datestamp']);
      $projects[$project_name]['sub_themes'] = array_merge($projects[$project_name]['sub_themes'], $sub_themes);
      $projects[$project_name]['base_themes'] = array_merge($projects[$project_name]['base_themes'], $base_themes);
    }
  }
}

/**
 * Given a $file object (as returned by system_get_files_database()), figure
 * out what project it belongs to.
 *
 * @see system_get_files_database()
 */
function update_get_project_name($file) {
  $project_name = '';
  if (isset($file->info['project'])) {
    $project_name = $file->info['project'];
  }
  elseif (isset($file->info['package']) && (strpos($file->info['package'], 'Core -') !== FALSE)) {
    $project_name = 'drupal';
  }
  elseif (in_array($file->name, array('bluemarine', 'chameleon', 'garland', 'marvin', 'minnelli', 'pushbutton'))) {
    // Unfortunately, there's no way to tell if a theme is part of core,
    // so we must hard-code a list here.
    $project_name = 'drupal';
  }
  return $project_name;
}

/**
 * Process the list of projects on the system to figure out the currently
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
        // .info in this case, and only if that's missing would we hit this.
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
 * Given the installed projects and the available release data retrieved from
 * remote servers, calculate the current status.
 *
 * This function is the heart of the update status feature. It iterates over
 * every currently installed project. For each one, it first checks if the
 * project has been flagged with a special status like "unsupported" or
 * "insecure", or if the project node itself has been unpublished. In any of
 * those cases, the project is marked with an error and the next project is
 * considered.
 *
 * If the project itself is valid, the function decides what major release
 * series to consider. The project defines what the currently supported major
 * versions are for each version of core, so the first step is to make sure
 * the current version is still supported. If so, that's the target version.
 * If the current version is unsupported, the project maintainer's recommended
 * major version is used. There's also a check to make sure that this function
 * never recommends an earlier release than the currently installed major
 * version.
 *
 * Given a target major version, it scans the available releases looking for
 * the specific release to recommend (avoiding beta releases and development
 * snapshots if possible). This is complicated to describe, but an example
 * will help clarify. For the target major version, find the highest patch
 * level. If there is a release at that patch level with no extra ("beta",
 * etc), then we recommend the release at that patch level with the most
 * recent release date. If every release at that patch level has extra (only
 * betas), then recommend the latest release from the previous patch
 * level. For example:
 *
 * 1.6-bugfix <-- recommended version because 1.6 already exists.
 * 1.6
 *
 * or
 *
 * 1.6-beta
 * 1.5 <-- recommended version because no 1.6 exists.
 * 1.4
 *
 * It also looks for the latest release from the same major version, even a
 * beta release, to display to the user as the "Latest version" option.
 * Additionally, it finds the latest official release from any higher major
 * versions that have been released to provide a set of "Also available"
 * options.
 *
 * Finally, and most importantly, it keeps scanning the release history until
 * it gets to the currently installed release, searching for anything marked
 * as a security update. If any security updates have been found between the
 * recommended release and the installed version, all of the releases that
 * included a security fix are recorded so that the site administrator can be
 * warned their site is insecure, and links pointing to the release notes for
 * each security update can be included (which, in turn, will link to the
 * official security announcements for each vulnerability).
 *
 * This function relies on the fact that the .xml release history data comes
 * sorted based on major version and patch level, then finally by release date
 * if there are multiple releases such as betas from the same major.patch
 * version (e.g. 5.x-1.5-beta1, 5.x-1.5-beta2, and 5.x-1.5). Development
 * snapshots for a given major version are always listed last.
 *
 * The results of this function are expensive to compute, especially on sites
 * with lots of modules or themes, since it involves a lot of comparisons and
 * other operations. Therefore, we cache the results into the {cache_update}
 * table using the 'update_project_data' cache ID. However, since this is not
 * the data about available updates fetched from the network, it is ok to
 * invalidate it somewhat quickly. If we keep this data for very long, site
 * administrators are more likely to see incorrect results if they upgrade to
 * a newer version of a module or theme but do not visit certain pages that
 * automatically clear this cache.
 *
 * @param $available
 *  Array of data about available project releases.
 *
 * @see update_get_available()
 * @see update_get_projects()
 * @see update_process_project_info()
 * @see update_project_cache()
 */
function update_calculate_project_data($available) {
  // Retrieve the projects from cache, if present.
  $projects = update_project_cache('update_project_data');
  // If $projects is empty, then the cache must be rebuilt.
  // Otherwise, return the cached data and skip the rest of the function.
  if (!empty($projects)) {
    return $projects;
  }
  $projects = update_get_projects();
  update_process_project_info($projects);
  foreach ($projects as $project => $project_info) {
    if (isset($available[$project])) {

      // If the project status is marked as something bad, there's nothing
      // else to consider.
      if (isset($available[$project]['project_status'])) {
        switch ($available[$project]['project_status']) {
          case 'insecure':
            $projects[$project]['status'] = UPDATE_NOT_SECURE;
            if (empty($projects[$project]['extra'])) {
              $projects[$project]['extra'] = array();
            }
            $projects[$project]['extra'][] = array(
              'class' => 'project-not-secure',
              'label' => t('Project not secure'),
              'data' => t('This project has been labeled insecure by the Drupal security team, and is no longer available for download. Immediately disabling everything included by this project is strongly recommended!'),
            );
            break;
          case 'unpublished':
          case 'revoked':
            $projects[$project]['status'] = UPDATE_REVOKED;
            if (empty($projects[$project]['extra'])) {
              $projects[$project]['extra'] = array();
            }
            $projects[$project]['extra'][] = array(
              'class' => 'project-revoked',
              'label' => t('Project revoked'),
              'data' => t('This project has been revoked, and is no longer available for download. Disabling everything included by this project is strongly recommended!'),
            );
            break;
          case 'unsupported':
            $projects[$project]['status'] = UPDATE_NOT_SUPPORTED;
            if (empty($projects[$project]['extra'])) {
              $projects[$project]['extra'] = array();
            }
            $projects[$project]['extra'][] = array(
              'class' => 'project-not-supported',
              'label' => t('Project not supported'),
              'data' => t('This project is no longer supported, and is no longer available for download. Disabling everything included by this project is strongly recommended!'),
            );
            break;
          case 'not-fetched':
            $projects[$project]['status'] = UPDATE_NOT_FETCHED;
            $projects[$project]['reason'] = t('Failed to fetch available update data');
            break;

          default:
            // Assume anything else (e.g. 'published') is valid and we should
            // perform the rest of the logic in this function.
            break;
        }
      }

      if (!empty($projects[$project]['status'])) {
        // We already know the status for this project, so there's nothing
        // else to compute. Just record everything else we fetched from the
        // XML file into our projects array and move to the next project.
        $projects[$project] += $available[$project];
        continue;
      }

      // Figure out the target major version.
      $existing_major = $project_info['existing_major'];
      $supported_majors = array();
      if (isset($available[$project]['supported_majors'])) {
        $supported_majors = explode(',', $available[$project]['supported_majors']);
      }
      elseif (isset($available[$project]['default_major'])) {
        // Older release history XML file without supported or recommended.
        $supported_majors[] = $available[$project]['default_major'];
      }

      if (in_array($existing_major, $supported_majors)) {
        // Still supported, stay at the current major version.
        $target_major = $existing_major;
      }
      elseif (isset($available[$project]['recommended_major'])) {
        // Since 'recommended_major' is defined, we know this is the new XML
        // format. Therefore, we know the current release is unsupported since
        // its major version was not in the 'supported_majors' list. We should
        // find the best release from the recommended major version.
        $target_major = $available[$project]['recommended_major'];
        $projects[$project]['status'] = UPDATE_NOT_SUPPORTED;
      }
      elseif (isset($available[$project]['default_major'])) {
        // Older release history XML file without recommended, so recommend
        // the currently defined "default_major" version.
        $target_major = $available[$project]['default_major'];
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

      $version_patch_changed = '';
      $patch = '';

      // Defend ourselves from XML history files that contain no releases.
      if (empty($available[$project]['releases'])) {
        $projects[$project]['status'] = UPDATE_UNKNOWN;
        $projects[$project]['reason'] = t('No available releases found');
        continue;
      }
      foreach ($available[$project]['releases'] as $version => $release) {
        // First, if this is the existing release, check a few conditions.
        if ($projects[$project]['existing_version'] === $version) {
          if (isset($release['terms']['Release type']) &&
              in_array('Insecure', $release['terms']['Release type'])) {
            $projects[$project]['status'] = UPDATE_NOT_SECURE;
          }
          elseif ($release['status'] == 'unpublished') {
            $projects[$project]['status'] = UPDATE_REVOKED;
            if (empty($projects[$project]['extra'])) {
              $projects[$project]['extra'] = array();
            }
            $projects[$project]['extra'][] = array(
              'class' => 'release-revoked',
              'label' => t('Release revoked'),
              'data' => t('Your currently installed release has been revoked, and is no longer available for download. Disabling everything included in this release or upgrading is strongly recommended!'),
            );
          }
          elseif (isset($release['terms']['Release type']) &&
                  in_array('Unsupported', $release['terms']['Release type'])) {
            $projects[$project]['status'] = UPDATE_NOT_SUPPORTED;
            if (empty($projects[$project]['extra'])) {
              $projects[$project]['extra'] = array();
            }
            $projects[$project]['extra'][] = array(
              'class' => 'release-not-supported',
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
        if ($release['version_major'] > $target_major) {
          if (in_array($release['version_major'], $supported_majors)) {
            if (!isset($available[$project]['also'])) {
              $available[$project]['also'] = array();
            }
            if (!isset($available[$project]['also'][$release['version_major']])) {
              $available[$project]['also'][$release['version_major']] = $version;
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
        if (!isset($available[$project]['latest_version'])
            && $release['version_major'] == $target_major) {
          $available[$project]['latest_version'] = $version;
        }

        // Look for the development snapshot release for this branch.
        if (!isset($available[$project]['dev_version'])
            && $release['version_major'] == $target_major
            && isset($release['version_extra'])
            && $release['version_extra'] == 'dev') {
          $available[$project]['dev_version'] = $version;
        }

        // Look for the 'recommended' version if we haven't found it yet (see
        // phpdoc at the top of this function for the definition).
        if (!isset($available[$project]['recommended'])
            && $release['version_major'] == $target_major
            && isset($release['version_patch'])) {
          if ($patch != $release['version_patch']) {
            $patch = $release['version_patch'];
            $version_patch_changed = $release['version'];
          }
          if (empty($release['version_extra']) && $patch == $release['version_patch']) {
            $available[$project]['recommended'] = $version_patch_changed;
          }
        }

        // Stop searching once we hit the currently installed version.
        if ($projects[$project]['existing_version'] === $version) {
          break;
        }

        // If we're running a dev snapshot and have a timestamp, stop
        // searching for security updates once we hit an official release
        // older than what we've got. Allow 100 seconds of leeway to handle
        // differences between the datestamp in the .info file and the
        // timestamp of the tarball itself (which are usually off by 1 or 2
        // seconds) so that we don't flag that as a new release.
        if ($projects[$project]['install_type'] == 'dev') {
          if (empty($projects[$project]['datestamp'])) {
            // We don't have current timestamp info, so we can't know.
            continue;
          }
          elseif (isset($release['date']) && ($projects[$project]['datestamp'] + 100 > $release['date'])) {
            // We're newer than this, so we can skip it.
            continue;
          }
        }

        // See if this release is a security update.
        if (isset($release['terms']['Release type'])
            && in_array('Security update', $release['terms']['Release type'])) {
          $projects[$project]['security updates'][] = $release;
        }
      }

      // If we were unable to find a recommended version, then make the latest
      // version the recommended version if possible.
      if (!isset($available[$project]['recommended']) && isset($available[$project]['latest_version'])) {
        $available[$project]['recommended'] = $available[$project]['latest_version'];
      }

      // Stash the info about available releases into our $projects array.
      $projects[$project] += $available[$project];

      //
      // Check to see if we need an update or not.
      //

      if (!empty($projects[$project]['security updates'])) {
        // If we found security updates, that always trumps any other status.
        $projects[$project]['status'] = UPDATE_NOT_SECURE;
      }

      if (isset($projects[$project]['status'])) {
        // If we already know the status, we're done.
        continue;
      }

      // If we don't know what to recommend, there's nothing we can report.
      // Bail out early.
      if (!isset($projects[$project]['recommended'])) {
        $projects[$project]['status'] = UPDATE_UNKNOWN;
        $projects[$project]['reason'] = t('No available releases found');
        continue;
      }

      // If we're running a dev snapshot, compare the date of the dev snapshot
      // with the latest official version, and record the absolute latest in
      // 'latest_dev' so we can correctly decide if there's a newer release
      // than our current snapshot.
      if ($projects[$project]['install_type'] == 'dev') {
        if (isset($available[$project]['dev_version']) && $available[$project]['releases'][$available[$project]['dev_version']]['date'] > $available[$project]['releases'][$available[$project]['latest_version']]['date']) {
          $projects[$project]['latest_dev'] = $available[$project]['dev_version'];
        }
        else {
          $projects[$project]['latest_dev'] = $available[$project]['latest_version'];
        }
      }

      // Figure out the status, based on what we've seen and the install type.
      switch ($projects[$project]['install_type']) {
        case 'official':
          if ($projects[$project]['existing_version'] === $projects[$project]['recommended'] || $projects[$project]['existing_version'] === $projects[$project]['latest_version']) {
            $projects[$project]['status'] = UPDATE_CURRENT;
          }
          else {
            $projects[$project]['status'] = UPDATE_NOT_CURRENT;
          }
          break;

        case 'dev':
          $latest = $available[$project]['releases'][$projects[$project]['latest_dev']];
          if (empty($projects[$project]['datestamp'])) {
            $projects[$project]['status'] = UPDATE_NOT_CHECKED;
            $projects[$project]['reason'] = t('Unknown release date');
          }
          elseif (($projects[$project]['datestamp'] + 100 > $latest['date'])) {
            $projects[$project]['status'] = UPDATE_CURRENT;
          }
          else {
            $projects[$project]['status'] = UPDATE_NOT_CURRENT;
          }
          break;

        default:
          $projects[$project]['status'] = UPDATE_UNKNOWN;
          $projects[$project]['reason'] = t('Invalid info');
      }
    }
    else {
      $projects[$project]['status'] = UPDATE_UNKNOWN;
      $projects[$project]['reason'] = t('No available releases found');
    }
  }
  // Give other modules a chance to alter the status (for example, to allow a
  // contrib module to provide fine-grained settings to ignore specific
  // projects or releases).
  drupal_alter('update_status', $projects);

  // Cache the site's update status for at most 1 hour.
  _update_cache_set('update_project_data', $projects, time() + 3600);
  return $projects;
}

/**
 * Retrieve data from {cache_update} or empty the cache when necessary.
 *
 * Two very expensive arrays computed by this module are the list of all
 * installed modules and themes (and .info data, project associations, etc),
 * and the current status of the site relative to the currently available
 * releases. These two arrays are cached in the {cache_update} table and used
 * whenever possible. The cache is cleared whenever the administrator visits
 * the status report, available updates report, or the module or theme
 * administration pages, since we should always recompute the most current
 * values on any of those pages.
 *
 * Note: while both of these arrays are expensive to compute (in terms of disk
 * I/O and some fairly heavy CPU processing), neither of these is the actual
 * data about available updates that we have to fetch over the network from
 * updates.drupal.org. That information is stored with the
 * 'update_available_releases' cache ID -- it needs to persist longer than 1
 * hour and never get invalidated just by visiting a page on the site.
 *
 * @param $cid
 *   The cache id of data to return from the cache. Valid options are
 *   'update_project_data' and 'update_project_projects'.
 *
 * @return
 *   The cached value of the $projects array generated by
 *   update_calculate_project_data() or update_get_projects(), or an empty
 *   array when the cache is cleared.
 */
function update_project_cache($cid) {
  $projects = array();

  // On certain paths, we should clear the cache and recompute the projects or
  // update status of the site to avoid presenting stale information.
  $q = $_GET['q'];
  $paths = array('admin/build/modules', 'admin/build/themes', 'admin/reports', 'admin/reports/updates', 'admin/reports/status', 'admin/reports/updates/check');
  if (in_array($q, $paths)) {
    _update_cache_clear($cid);
  }
  else {
    $cache = _update_cache_get($cid);
    if (!empty($cache->data) && $cache->expire > time()) {
      $projects = $cache->data;
    }
  }
  return $projects;
}

/**
 * Filter the project .info data to only save attributes we need.
 *
 * @param array $info
 *   Array of .info file data as returned by drupal_parse_info_file().
 *
 * @return
 *   Array of .info file data we need for the Update manager.
 *
 * @see _update_process_info_list()
 */
function update_filter_project_info($info) {
  $whitelist = array(
    '_info_file_ctime',
    'datestamp',
    'major',
    'name',
    'package',
    'project',
    'project status url',
    'version',
  );
  $whitelist = array_flip($whitelist);
  foreach ($info as $key => $value) {
    if (!isset($whitelist[$key])) {
      unset($info[$key]);
    }
  }
  return $info;
}
