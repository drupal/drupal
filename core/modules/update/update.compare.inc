<?php

/**
 * @file
 */

use Drupal\update\UpdateCalculator;
use Drupal\update\UpdateManagerInterface;
use Drupal\update\UpdateProject;
use Drupal\update\UpdateServerProjectInfo;

/**
 * Determines version and type information for currently installed projects.
 *
 * Processes the list of projects on the system to figure out the currently
 * installed versions, and other information that is required before we can
 * compare against the available releases to produce the status report.
 *
 * @param array $projects
 *   Array of project information from
 *   \Drupal\update\UpdateManagerInterface::getProjects().
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(UpdateCalculator::class)->processProjectInfo() instead.
 *
 * @see https://www.drupal.org/node/3587768
 */
function update_process_project_info(&$projects): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use \Drupal::service(UpdateCalculator::class)->processProjectInfo() instead. See https://www.drupal.org/node/3587768', E_USER_DEPRECATED);
  \Drupal::service(UpdateCalculator::class)->processProjectInfo($projects);
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
 * @see \Drupal\update\UpdateManagerInterface::getAvailable()
 * @see \Drupal\update\UpdateManagerInterface::getProjects()
 * @see \Drupal\update\UpdateCalculator::processProjectInfo()
 * @see \Drupal\update\UpdateManagerInterface::projectStorage()
 * @see \Drupal\update\ProjectCoreCompatibility::setReleaseMessage()
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(UpdateManagerInterface::class)
 *   ->calculateProjectData($available) instead.
 *
 * @see https://www.drupal.org/node/3587768
 */
function update_calculate_project_data($available) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use \Drupal::service(UpdateManagerInterface::class)->calculateProjectData($available) instead. See https://www.drupal.org/node/3587768', E_USER_DEPRECATED);
  return \Drupal::service(UpdateManagerInterface::class)->calculateProjectData($available);
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
 * @param array $project_data
 *   An array containing information about a specific project.
 * @param array $available
 *   Data about available project releases of a specific project.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(UpdateCalculator::class)->updateProjectStatus() instead.
 *   The parameters have changed to use Drupal\update\UpdateProject and
 *   Drupal\update\UpdateServerProjectInfo.
 *
 * @see https://www.drupal.org/node/3587768
 */
function update_calculate_project_update_status(&$project_data, $available): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use \Drupal::service(UpdateCalculator::class)->updateProjectStatus() instead. The parameters have changed to use Drupal\update\UpdateProject and Drupal\update\UpdateServerProjectInfo. See https://www.drupal.org/node/3587768', E_USER_DEPRECATED);
  $project_data = \Drupal::service(UpdateCalculator::class)->updateProjectStatus(UpdateProject::createFromArray($project_data), UpdateServerProjectInfo::createFromArray($available))->toArray();
}
