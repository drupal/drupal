<?php

namespace Drupal\Core\Utility;

use Drupal\Core\Extension\Extension;

/**
 * Performs operations on drupal.org project data.
 */
class ProjectInfo {

  /**
   * Populates an array of project data.
   *
   * This iterates over a list of the installed modules or themes and groups
   * them by project and status. A few parts of this function assume that
   * enabled modules and themes are always processed first, and if uninstalled
   * modules or themes are being processed (there is a setting to control if
   * uninstalled code should be included in the Available updates report or
   * not),those are only processed after $projects has been populated with
   * information about the enabled code. 'Hidden' modules and themes are
   * ignored if they are not installed. 'Hidden' Modules and themes in the
   * "Testing" package are ignored regardless of installation status.
   *
   * This function also records the latest change time on the .info.yml files
   * for each module or theme, which is important data which is used when
   * deciding if the available update data should be invalidated.
   *
   * @param array $projects
   *   Reference to the array of project data of what's installed on this site.
   * @param \Drupal\Core\Extension\Extension[] $list
   *   Array of data to process to add the relevant info to the $projects array.
   * @param string $project_type
   *   The kind of data in the list. Can be 'module' or 'theme'.
   * @param bool $status
   *   Boolean that controls what status (enabled or uninstalled) to process out
   *   of the $list and add to the $projects array.
   * @param array $additional_elements
   *   (optional) Array of additional elements to be collected from the .info.yml
   *   file. Defaults to array().
   */
  public function processInfoList(array &$projects, array $list, $project_type, $status, array $additional_elements = []) {
    foreach ($list as $file) {
      // Just projects with a matching status should be listed.
      if ($file->status != $status) {
        continue;
      }

      // Skip if the .info.yml file is broken.
      if (empty($file->info)) {
        continue;
      }

      // Skip if it's a hidden project and the project is not installed.
      if (!empty($file->info['hidden']) && empty($status)) {
        continue;
      }

      // Skip if it's a hidden project and the project is a test project. Tests
      // should use hook_system_info_alter() to test ProjectInfo's
      // functionality.
      if (!empty($file->info['hidden']) && isset($file->info['package']) && $file->info['package'] == 'Testing') {
        continue;
      }

      // If the .info.yml doesn't define the 'project', try to figure it out.
      if (!isset($file->info['project'])) {
        $file->info['project'] = $this->getProjectName($file);
      }

      // If we still don't know the 'project', give up.
      if (empty($file->info['project'])) {
        continue;
      }

      // If we don't already know it, grab the change time on the .info.yml file
      // itself. Note: we need to use the ctime, not the mtime (modification
      // time) since many (all?) tar implementations will go out of their way to
      // set the mtime on the files it creates to the timestamps recorded in the
      // tarball. We want to see the last time the file was changed on disk,
      // which is left alone by tar and correctly set to the time the .info.yml
      // file was unpacked.
      if (!isset($file->info['_info_file_ctime'])) {
        $file->info['_info_file_ctime'] = $file->getCTime();
      }

      if (!isset($file->info['datestamp'])) {
        $file->info['datestamp'] = 0;
      }

      $project_name = $file->info['project'];

      // Figure out what project type we're going to use to display this module
      // or theme. If the project name is 'drupal', we don't want it to show up
      // under the usual "Modules" section, we put it at a special "Drupal Core"
      // section at the top of the report.
      if ($project_name == 'drupal') {
        $project_display_type = 'core';
      }
      else {
        $project_display_type = $project_type;
      }
      if (empty($status)) {
        // If we're processing uninstalled modules or themes, append a suffix.
        $project_display_type .= '-disabled';
      }
      if (!isset($projects[$project_name])) {
        // Only process this if we haven't done this project, since a single
        // project can have multiple modules or themes.
        $projects[$project_name] = [
          'name' => $project_name,
          // Only save attributes from the .info.yml file we care about so we do
          // not bloat our RAM usage needlessly.
          'info' => $this->filterProjectInfo($file->info, $additional_elements),
          'datestamp' => $file->info['datestamp'],
          'includes' => [$file->getName() => $file->info['name']],
          'project_type' => $project_display_type,
          'project_status' => $status,
        ];
      }
      elseif ($projects[$project_name]['project_type'] == $project_display_type) {
        // Only add the file we're processing to the 'includes' array for this
        // project if it is of the same type and status (which is encoded in the
        // $project_display_type). This prevents listing all the uninstalled
        // modules included with an enabled project if we happen to be checking
        // for uninstalled modules, too.
        $projects[$project_name]['includes'][$file->getName()] = $file->info['name'];
        $projects[$project_name]['info']['_info_file_ctime'] = max($projects[$project_name]['info']['_info_file_ctime'], $file->info['_info_file_ctime']);
        $projects[$project_name]['datestamp'] = max($projects[$project_name]['datestamp'], $file->info['datestamp']);
      }
      elseif (empty($status)) {
        // If we have a project_name that matches, but the project_display_type
        // does not, it means we're processing an uninstalled module or theme
        // that belongs to a project that has some enabled code. In this case,
        // we add the uninstalled thing into a separate array for separate
        // display.
        $projects[$project_name]['disabled'][$file->getName()] = $file->info['name'];
      }
    }
  }

  /**
   * Determines what project a given file object belongs to.
   *
   * @param \Drupal\Core\Extension\Extension $file
   *   An extension object.
   *
   * @return string
   *   The canonical project short name.
   */
  public function getProjectName(Extension $file) {
    $project_name = '';
    if (isset($file->info['project'])) {
      $project_name = $file->info['project'];
    }
    elseif (strpos($file->getPath(), 'core/modules') === 0) {
      $project_name = 'drupal';
    }
    return $project_name;
  }

  /**
   * Filters the project .info.yml data to only save attributes we need.
   *
   * @param array $info
   *   Array of .info.yml file data as returned by
   *   \Drupal\Core\Extension\InfoParser.
   * @param $additional_elements
   *   (optional) Array of additional elements to be collected from the .info.yml
   *   file. Defaults to array().
   *
   * @return array
   *   Array of .info.yml file data we need for the update manager.
   *
   * @see \Drupal\Core\Utility\ProjectInfo::processInfoList()
   */
  public function filterProjectInfo($info, $additional_elements = []) {
    $elements = [
      '_info_file_ctime',
      'datestamp',
      'major',
      'name',
      'package',
      'project',
      'project status url',
      'version',
    ];
    $elements = array_merge($elements, $additional_elements);
    return array_intersect_key($info, array_combine($elements, $elements));
  }

}
