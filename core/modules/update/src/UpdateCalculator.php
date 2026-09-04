<?php

namespace Drupal\update;

use Drupal\Core\Extension\ExtensionVersion;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Calculate project update data.
 *
 * This will load the releases available from the update server, it will parse
 * the versions available, security status and what the recommended version is.
 *
 * @internal
 */
class UpdateCalculator {

  use StringTranslationTrait;

  public function __construct(
    #[AutowireServiceClosure('logger.channel.update')]
    protected readonly \Closure $updateLogger,
  ) {}

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
   */
  public function processProjectInfo(array &$projects): void {
    foreach ($projects as $key => $project) {
      // Assume an official release until we see otherwise.
      $installType = 'official';

      $info = $project['info'];

      if (isset($info['version'])) {
        // Check for development snapshots.
        if (preg_match('@(dev|HEAD)@', $info['version'])) {
          $installType = 'dev';
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
        $installType = 'unknown';
        $info['version'] = $this->t('Unknown');
        $info['major'] = -1;
      }

      // Finally, save the results we care about into the $projects array.
      $projects[$key]['existing_version'] = $info['version'];
      $projects[$key]['existing_major'] = $info['major'];
      $projects[$key]['install_type'] = $installType;
    }
  }

  /**
   * Calculates the current update status of a specific project.
   *
   * This method is the heart of the update status feature. For each project
   * it is invoked with, it first checks if the project has been flagged with a
   * special status like "unsupported" or "insecure", or if the project node
   * itself has been unpublished. In any of those cases, the project is marked
   * with an error and the next project is considered.
   *
   * If the project itself is valid, the method decides what major release
   * series to consider. The project defines its currently supported branches in
   * its Drupal.org for the project, so the first step is to make sure the
   * development branch of the current version is still supported. If so, then
   * the major version of the current version is used. If the current version
   * is not in a supported branch, the next supported branch is used to
   * determine the major version to use. There's also a check to make sure that
   * this method never recommends an earlier release than the currently
   * installed major version.
   *
   * Given a target major version, the available releases are scanned looking
   * for the specific release to recommend (avoiding beta releases and
   * development snapshots if possible). For the target major version, the
   * highest patch level is found. If there is a release at that patch level
   * with no extra ("beta", etc.), then the release at that patch level with
   * the most recent release date is recommended. If every release at that
   * patch level has extra (only betas), then the latest release from the
   * previous patch level is recommended. For example:
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
   * Also, the latest release from the same major version is looked for, even
   * beta releases, to display to the user as the "Latest version" option.
   * Additionally, the latest official release from any higher major versions
   * that have been released is searched for to provide a set of "Also
   * available" options.
   *
   * Finally, and most importantly, the release history continues to be scanned
   * until the currently installed release is reached, searching for anything
   * marked as a security update. If any security updates have been found
   * between the recommended release and the installed version, all of the
   * releases that included a security fix are recorded so that the site
   * administrator can be warned their site is insecure, and links pointing to
   * the release notes for each security update can be included (which, in
   * turn, will link to the official security announcements for each
   * vulnerability).
   *
   * This method relies on the fact that the .xml release history data comes
   * sorted based on major version and patch level, then finally by release date
   * if there are multiple releases such as betas from the same major.patch
   * version (e.g., 5.x-1.5-beta1, 5.x-1.5-beta2, and 5.x-1.5). Development
   * snapshots for a given major version are always listed last.
   *
   * NOTE: This method *must* set a value for $projectData->status before
   * returning, or the rest of the Update Manager will break in unexpected ways.
   *
   * @param \Drupal\update\UpdateProject $projectData
   *   An array containing information about a specific project.
   * @param \Drupal\update\UpdateServerProjectInfo $available
   *   Data about available project releases of a specific project.
   *
   * @return \Drupal\update\UpdateProject
   *   The updated UpdateProject.
   */
  public function updateProjectStatus(UpdateProject $projectData, UpdateServerProjectInfo $available): UpdateProject {
    foreach (['title', 'link'] as $attribute) {
      if (!isset($projectData->$attribute) && isset($available->$attribute)) {
        $projectData->$attribute = $available->$attribute;
      }
    }

    // If the project status is marked as something bad, there's nothing else
    // to consider.
    switch ($available->status ?? NULL) {
      case 'insecure':
        $projectData->status = UpdateManagerInterface::NOT_SECURE;
        $projectData->extra[] = [
          'label' => $this->t('Project not secure'),
          'data' => $this->t('This project has been labeled insecure by the Drupal security team, and is no longer available for download. Immediately uninstalling everything included by this project is strongly recommended!'),
        ];
        break;

      case 'unpublished':
      case 'revoked':
        $projectData->status = UpdateManagerInterface::REVOKED;
        $projectData->extra[] = [
          'label' => $this->t('Project revoked'),
          'data' => $this->t('This project has been revoked, and is no longer available for download. Uninstalling everything included by this project is strongly recommended!'),
        ];
        break;

      case 'unsupported':
        $projectData->status = UpdateManagerInterface::NOT_SUPPORTED;
        $projectData->extra[] = [
          'label' => $this->t('Project not supported'),
          'data' => $this->t('This project is no longer supported, and is no longer available for download. Uninstalling everything included by this project is strongly recommended!'),
        ];
        break;

      case 'not-fetched':
        $projectData->status = UpdateFetcherInterface::NOT_FETCHED;
        $projectData->reason = $this->t('Failed to get available update data.');
        break;

      default:
        // Assume anything else (e.g. 'published') is valid and we should
        // perform the rest of the logic in this method.
        break;
    }

    if (!empty($projectData->status)) {
      // We already know the status for this project, so there's nothing else to
      // compute. Record the project status into $projectData and we're done.
      $projectData->projectStatus = $available->status;
      return $projectData;
    }

    // Figure out the target major version.
    // Off Drupal.org, '0' could be a valid version string, so don't use
    // empty().
    if (!isset($projectData->existingVersion) || $projectData->existingVersion === '') {
      $projectData->status = UpdateFetcherInterface::UNKNOWN;
      $projectData->reason = $this->t('Empty version');
      return $projectData;
    }
    try {
      $existingMajor = ExtensionVersion::createFromVersionString($projectData->existingVersion)->getMajorVersion();
    }
    catch (\UnexpectedValueException $exception) {
      // If the version has an unexpected value we can't determine updates.
      $projectData->status = UpdateFetcherInterface::UNKNOWN;
      $projectData->reason = $this->t('Invalid version: @existingVersion', ['@existingVersion' => $projectData->existingVersion]);
      return $projectData;
    }
    $supportedBranches = $available->supportedBranches;

    $isInSupportedBranch = function ($version) use ($supportedBranches) {
      foreach ($supportedBranches as $supportedBranch) {
        if (str_starts_with($version, $supportedBranch)) {
          return TRUE;
        }
      }
      return FALSE;
    };
    if ($isInSupportedBranch($projectData->existingVersion)) {
      // Still supported, stay at the current major version.
      $targetMajor = $existingMajor;
    }
    elseif ($supportedBranches) {
      // We know the current release is unsupported since it is not in
      // 'supported_branches' list. We should use the next valid supported
      // branch for the target major version.
      $projectData->status = UpdateManagerInterface::NOT_SUPPORTED;
      foreach ($supportedBranches as $supportedBranch) {
        try {
          $targetMajor = ExtensionVersion::createFromSupportBranch($supportedBranch)->getMajorVersion();
          break;
        }
        catch (\UnexpectedValueException $exception) {
          continue;
        }
      }
      if (!isset($targetMajor)) {
        // If there are no valid support branches, use the current major.
        $targetMajor = $existingMajor;
      }
    }
    else {
      // Malformed XML file? Stick with the current branch.
      $targetMajor = $existingMajor;
    }

    // Make sure we never tell the admin to downgrade. If we recommended an
    // earlier version than the one they're running, they'd face an
    // impossible data migration problem, since Drupal never supports a DB
    // downgrade path. In the unfortunate case that what they're running is
    // unsupported, and there's nothing newer for them to upgrade to, we
    // can't print out a "Recommended version", but just have to tell them
    // what they have is unsupported and let them figure it out.
    $targetMajor = max($existingMajor, $targetMajor);

    // If the project is marked as UpdateFetcherInterface::FETCH_PENDING, it
    // means that the data we currently have (if any) is stale, and we've got a
    // task queued up to (re)fetch the data. In that case, we mark it as such,
    // merge in whatever data we have (e.g. project title and link), and move
    // on.
    if ($available->fetchStatus == UpdateFetcherInterface::FETCH_PENDING) {
      $projectData->status = UpdateFetcherInterface::FETCH_PENDING;
      $projectData->reason = $this->t('No available update data');
      $projectData->fetchStatus = $available->fetchStatus;
      return $projectData;
    }

    // Defend ourselves from XML history files that contain no releases.
    if (empty($available->releases)) {
      $projectData->status = UpdateFetcherInterface::UNKNOWN;
      $projectData->reason = $this->t('No available releases found');
      return $projectData;
    }

    $recommendedVersionWithoutExtra = '';
    $recommendedRelease = NULL;
    $releaseIsSupported = FALSE;
    foreach ($available->releases as $version => $releaseInfo) {
      try {
        $release = ProjectRelease::createFromArray($releaseInfo);
      }
      catch (\UnexpectedValueException $exception) {
        // Ignore releases that are in an invalid format. Although this is
        // highly unlikely we should still process releases in the correct
        // format.
        Error::logException(($this->updateLogger)(), $exception, 'Invalid project format: @release', ['@release' => print_r($releaseInfo, TRUE)]);
        continue;
      }

      try {
        $releaseModuleVersion = ExtensionVersion::createFromVersionString($release->getVersion());
      }
      catch (\UnexpectedValueException) {
        continue;
      }
      // This release is supported only if it is in a supported branch and is
      // not unsupported.
      $releaseIsSupported = $isInSupportedBranch($release->getVersion()) && !$release->isUnsupported();
      // First, if this is the existing release, check a few conditions.
      if ($projectData->existingVersion === $version) {
        if ($release->isInsecure()) {
          $projectData->status = UpdateManagerInterface::NOT_SECURE;
        }
        elseif (!$release->isPublished()) {
          $projectData->status = UpdateManagerInterface::REVOKED;
          $projectData->extra[] = [
            'class' => ['release-revoked'],
            'label' => $this->t('Release revoked'),
            'data' => $this->t('Your currently installed release has been revoked, and is no longer available for download. Uninstalling everything included in this release or upgrading is strongly recommended!'),
          ];
        }
        elseif (!$releaseIsSupported) {
          $projectData->status = UpdateManagerInterface::NOT_SUPPORTED;
          if (empty($projectData->recommended) && empty($projectData->also)) {
            $unsupportedMessage = $this->t('Your currently installed release is now unsupported, is no longer available for download and no update is available. Uninstalling everything included in this release is strongly recommended!');
          }
          else {
            $unsupportedMessage = $this->t('Your currently installed release is now unsupported, and is no longer available for download. Uninstalling everything included in this release or upgrading is strongly recommended!');
          }
          $projectData->extra[] = [
            'class' => ['release-not-supported'],
            'label' => $this->t('Release not supported'),
            'data' => $unsupportedMessage,
          ];
        }
      }
      // Other than the currently installed release, ignore unpublished,
      // insecure, or unsupported updates.
      elseif (
        !$release->isPublished() ||
        !$releaseIsSupported ||
        $release->isInsecure()
      ) {
        continue;
      }
      // Ignore dev releases with no date. These are either broken releases or
      // stub releases to allow them to be selected on drupal.org project
      // issues.
      elseif ($release->getDate() === NULL && $releaseModuleVersion->getVersionExtra() === 'dev') {
        continue;
      }

      $releaseMajorVersion = $releaseModuleVersion->getMajorVersion();
      // See if this is a higher major version than our target and yet still
      // supported. If so, record it as an "Also available" release.
      if ($releaseMajorVersion > $targetMajor) {
        if (!isset($projectData->also[$releaseMajorVersion])) {
          $projectData->also[$releaseMajorVersion] = $version;
          $projectData->releases[$version] = $releaseInfo;
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
      if (
        !isset($projectData->latestVersion)
        && $releaseMajorVersion == $targetMajor
      ) {
        $projectData->latestVersion = $version;
        $projectData->releases[$version] = $releaseInfo;
      }

      // Look for the development snapshot release for this branch.
      if (
        !isset($projectData->devVersion)
        && $releaseMajorVersion == $targetMajor
        && $releaseModuleVersion->getVersionExtra() === 'dev'
      ) {
        $projectData->devVersion = $version;
        $projectData->releases[$version] = $releaseInfo;
      }

      if ($releaseModuleVersion->getVersionExtra()) {
        $releaseVersionWithoutExtra = str_replace('-' . $releaseModuleVersion->getVersionExtra(), '', $release->getVersion());
      }
      else {
        $releaseVersionWithoutExtra = $release->getVersion();
      }

      // Look for the 'recommended' version if we haven't found it yet (see
      // PHPDoc at the top of this method for the definition).
      if (
        !isset($projectData->recommended)
        && $releaseMajorVersion == $targetMajor && $releaseIsSupported
      ) {
        if ($recommendedVersionWithoutExtra !== $releaseVersionWithoutExtra) {
          $recommendedVersionWithoutExtra = $releaseVersionWithoutExtra;
          $recommendedRelease = $releaseInfo;
        }
        if ($releaseModuleVersion->getVersionExtra() === NULL) {
          $projectData->recommended = $recommendedRelease['version'];
          $projectData->releases[$recommendedRelease['version']] = $recommendedRelease;
        }
      }

      // Stop searching once we hit the currently installed version.
      if ($projectData->existingVersion === $version) {
        break;
      }

      // If we're running a dev snapshot and have a timestamp, stop
      // searching for security updates once we hit an official release
      // older than what we've got. Allow 100 seconds of leeway to handle
      // differences between the datestamp in the .info.yml file and the
      // timestamp of the tarball itself (which are usually off by 1 or 2
      // seconds) so that we don't flag that as a new release.
      if ($projectData->installType == 'dev') {
        if (empty($projectData->datestamp)) {
          // We don't have current timestamp info, so we can't know.
          continue;
        }
        elseif ($release->getDate() && $projectData->datestamp + 100 > $release->getDate()) {
          // We're newer than this, so we can skip it.
          continue;
        }
      }

      if ($release->isSecurityRelease()) {
        $projectData->securityUpdates[] = $releaseInfo;
      }
    }

    // If we were unable to find a recommended version, then make the latest
    // version the recommended version if possible.
    if (!isset($projectData->recommended) && isset($projectData->latestVersion) && $releaseIsSupported) {
      $projectData->recommended = $projectData->latestVersion;
    }

    if (isset($projectData->status)) {
      // If we already know the status, we're done.
      return $projectData;
    }

    // If we don't know what to recommend, there's nothing we can report.
    // Bail out early.
    if (!isset($projectData->recommended)) {
      $projectData->status = UpdateFetcherInterface::UNKNOWN;
      $projectData->reason = $this->t('No available releases found');
      return $projectData;
    }

    // If we're running a dev snapshot, compare the date of the dev snapshot
    // with the latest official version, and record the absolute latest in
    // 'latest_dev' so we can correctly decide if there's a newer release
    // than our current snapshot.
    if ($projectData->installType == 'dev') {
      if (isset($projectData->devVersion) && $available->releases[$projectData->devVersion]['date'] > $available->releases[$projectData->latestVersion]['date']) {
        $projectData->latestDev = $projectData->devVersion;
      }
      else {
        $projectData->latestDev = $projectData->latestVersion;
      }
    }

    // Figure out the status, based on what we've seen and the install type.
    switch ($projectData->installType) {
      case 'official':
        if ($projectData->existingVersion === $projectData->recommended || $projectData->existingVersion === $projectData->latestVersion) {
          $projectData->status = UpdateManagerInterface::CURRENT;
        }
        else {
          $projectData->status = UpdateManagerInterface::NOT_CURRENT;
        }
        break;

      case 'dev':
        $latest = $available->releases[$projectData->latestDev];
        if (empty($projectData->datestamp)) {
          $projectData->status = UpdateFetcherInterface::NOT_CHECKED;
          $projectData->reason = $this->t('Unknown release date');
        }
        elseif (($projectData->datestamp + 100 > $latest['date'])) {
          $projectData->status = UpdateManagerInterface::CURRENT;
        }
        else {
          $projectData->status = UpdateManagerInterface::NOT_CURRENT;
        }
        break;

      default:
        $projectData->status = UpdateFetcherInterface::UNKNOWN;
        $projectData->reason = $this->t('Invalid info');
    }

    return $projectData;
  }

}
