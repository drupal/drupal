<?php

namespace Drupal\system\SecurityAdvisories;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Error;
use Drupal\Core\Utility\ProjectInfo;
use Drupal\Core\Extension\ExtensionVersion;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

/**
 * Defines a service to get security advisories.
 */
final class SecurityAdvisoriesFetcher {

  /**
   * The key to use to store the advisories feed response.
   */
  protected const ADVISORIES_JSON_EXPIRABLE_KEY = 'advisories_response';

  /**
   * The 'system.advisories' configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The expirable key/value store for the advisories JSON response.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * Array of extension lists, keyed by extension type.
   *
   * @var \Drupal\Core\Extension\ExtensionList[]
   */
  protected $extensionLists = [];

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Whether to fall back to HTTP if the HTTPS request fails.
   *
   * @var bool
   */
  protected $withHttpFallback;

  /**
   * Constructs a new SecurityAdvisoriesFetcher object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_factory
   *   The expirable key/value factory.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module extension list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   *   The theme extension list.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profile_list
   *   The profile extension list.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyValueExpirableFactoryInterface $key_value_factory, ClientInterface $client, ModuleExtensionList $module_list, ThemeExtensionList $theme_list, ProfileExtensionList $profile_list, LoggerInterface $logger, Settings $settings) {
    $this->config = $config_factory->get('system.advisories');
    $this->keyValueExpirable = $key_value_factory->get('system');
    $this->httpClient = $client;
    $this->extensionLists['module'] = $module_list;
    $this->extensionLists['theme'] = $theme_list;
    $this->extensionLists['profile'] = $profile_list;
    $this->logger = $logger;
    $this->withHttpFallback = $settings->get('update_fetch_with_http_fallback', FALSE);
  }

  /**
   * Gets security advisories that are applicable for the current site.
   *
   * @param bool $allow_outgoing_request
   *   (optional) Whether to allow an outgoing request to fetch the advisories
   *   if there is no stored JSON response. Defaults to TRUE.
   * @param int $timeout
   *   (optional) The timeout in seconds for the request. Defaults to 0, which
   *   is no timeout.
   *
   * @return \Drupal\system\SecurityAdvisories\SecurityAdvisory[]|null
   *   The upstream security advisories, if any. NULL if there was a problem
   *   retrieving the JSON feed, or if there was no stored response and
   *   $allow_outgoing_request was set to FALSE.
   *
   * @throws \GuzzleHttp\Exception\TransferException
   *   Thrown if an error occurs while retrieving security advisories.
   */
  public function getSecurityAdvisories(bool $allow_outgoing_request = TRUE, int $timeout = 0): ?array {
    $advisories = [];

    $json_payload = $this->keyValueExpirable->get(self::ADVISORIES_JSON_EXPIRABLE_KEY);
    // If $json_payload is not an array then it was not set in this method or
    // has expired in which case we should try to retrieve the advisories.
    if (!is_array($json_payload)) {
      if (!$allow_outgoing_request) {
        return NULL;
      }
      $response = $this->doRequest($timeout);
      $interval_seconds = $this->config->get('interval_hours') * 60 * 60;
      $json_payload = Json::decode($response);
      if (is_array($json_payload)) {
        // Only store and use the response if it could be successfully
        // decoded to an array from the JSON string.
        // This value will be deleted if the 'advisories.interval_hours' config
        // is changed to a lower value.
        // @see \Drupal\update\EventSubscriber\ConfigSubscriber::onConfigSave()
        $this->keyValueExpirable->setWithExpire(self::ADVISORIES_JSON_EXPIRABLE_KEY, $json_payload, $interval_seconds);
      }
      else {
        $this->logger->error('The security advisory JSON feed from Drupal.org could not be decoded.');
        return NULL;
      }
    }

    foreach ($json_payload as $advisory_data) {
      try {
        $sa = SecurityAdvisory::createFromArray($advisory_data);
      }
      catch (\UnexpectedValueException $unexpected_value_exception) {
        // Ignore items in the feed that are in an invalid format. Although
        // this is highly unlikely we should still display the items that are
        // in the correct format.
        Error::logException($this->logger, $unexpected_value_exception, 'Invalid security advisory format: @advisory', ['@advisory' => Json::encode($advisory_data)]);
        continue;
      }

      if ($this->isApplicable($sa)) {
        $advisories[] = $sa;
      }
    }
    return $advisories;
  }

  /**
   * Deletes the stored JSON feed response, if any.
   */
  public function deleteStoredResponse(): void {
    $this->keyValueExpirable->delete(self::ADVISORIES_JSON_EXPIRABLE_KEY);
  }

  /**
   * Determines if an advisory matches the existing version of a project.
   *
   * @param \Drupal\system\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return bool
   *   TRUE if the security advisory matches the existing version of the
   *   project, or FALSE otherwise.
   */
  protected function matchesExistingVersion(SecurityAdvisory $sa): bool {
    if ($existing_version = $this->getProjectExistingVersion($sa)) {
      $existing_project_version = ExtensionVersion::createFromVersionString($existing_version);
      $insecure_versions = $sa->getInsecureVersions();
      // If a site codebase has a development version of any project, including
      // core, we cannot be certain if their development build has the security
      // vulnerabilities that make any of the versions in $insecure_versions
      // insecure. Therefore, we should err on the side of assuming the site's
      // code does have the security vulnerabilities and show the advisories.
      // This will result in some sites seeing advisories that do not affect
      // their versions, but it will make it less likely that sites with the
      // security vulnerabilities will not see the advisories.
      if ($existing_project_version->getVersionExtra() === 'dev') {
        foreach ($insecure_versions as $insecure_version) {
          try {
            $insecure_project_version = ExtensionVersion::createFromVersionString($insecure_version);
          }
          catch (\UnexpectedValueException $exception) {
            // An invalid version string should not halt the evaluation of valid
            // versions in $insecure_versions. Version numbers that start with
            // core prefix besides '8.x-' are allowed in $insecure_versions,
            // but will never match and will throw an exception.
            continue;
          }
          if ($existing_project_version->getMajorVersion() === $insecure_project_version->getMajorVersion()) {
            if ($existing_project_version->getMinorVersion() === NULL) {
              // If the dev version doesn't specify a minor version, matching on
              // the major version alone is considered a match.
              return TRUE;
            }
            if ($existing_project_version->getMinorVersion() === $insecure_project_version->getMinorVersion()) {
              // If the dev version specifies a minor version, then the insecure
              // version must match on the minor version.
              return TRUE;
            }
          }
        }
      }
      else {
        // If the existing version is not a dev version, then it must match an
        // insecure version exactly.
        return in_array($existing_version, $insecure_versions, TRUE);
      }
    }
    return FALSE;
  }

  /**
   * Gets the information for an extension affected by the security advisory.
   *
   * @param \Drupal\system\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return mixed[]|null
   *   The information as set in the info.yml file and then processed by the
   *   corresponding extension list for the first extension found that matches
   *   the project name of the security advisory. If no matching extension is
   *   found NULL is returned.
   */
  protected function getMatchingExtensionInfo(SecurityAdvisory $sa): ?array {
    if (!isset($this->extensionLists[$sa->getProjectType()])) {
      return NULL;
    }
    $project_info = new ProjectInfo();
    // The project name on the security advisory will not always match the
    // machine name for the extension, so we need to search through all
    // extensions of the expected type to find the matching project.
    foreach ($this->extensionLists[$sa->getProjectType()]->getList() as $extension) {
      if ($project_info->getProjectName($extension) === $sa->getProject()) {
        return $extension->info;
      }
    }
    return NULL;
  }

  /**
   * Gets the existing project version.
   *
   * @param \Drupal\system\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return string|null
   *   The project version, or NULL if the project does not exist on
   *   the site.
   */
  protected function getProjectExistingVersion(SecurityAdvisory $sa): ?string {
    if ($sa->isCoreAdvisory()) {
      return \Drupal::VERSION;
    }
    $extension_info = $this->getMatchingExtensionInfo($sa);
    return $extension_info['version'] ?? NULL;
  }

  /**
   * Determines if a security advisory is applicable for the current site.
   *
   * @param \Drupal\system\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return bool
   *   TRUE if the advisory is applicable for the current site, or FALSE
   *   otherwise.
   */
  protected function isApplicable(SecurityAdvisory $sa): bool {
    // Only projects that are in the site's codebase can be applicable. Core
    // will always be in the codebase, and other projects are in the codebase if
    // ::getProjectInfo() finds a matching extension for the project name.
    if ($sa->isCoreAdvisory() || $this->getMatchingExtensionInfo($sa)) {
      // Public service announcements are always applicable because they are not
      // dependent on the version of the project that is currently present on
      // the site. Other advisories are only applicable if they match the
      // existing version.
      return $sa->isPsa() || $this->matchesExistingVersion($sa);
    }
    return FALSE;
  }

  /**
   * Makes an HTTPS GET request, with a possible HTTP fallback.
   *
   * This method will fall back to HTTP if the HTTPS request fails and the site
   * setting 'update_fetch_with_http_fallback' is set to TRUE.
   *
   * @param int $timeout
   *   The timeout in seconds for the request.
   *
   * @return string
   *   The response.
   */
  protected function doRequest(int $timeout): string {
    $options = [RequestOptions::TIMEOUT => $timeout];
    if (!$this->withHttpFallback) {
      // If not using an HTTP fallback just use HTTPS and do not catch any
      // exceptions.
      $response = $this->httpClient->get('https://updates.drupal.org/psa.json', $options);
    }
    else {
      try {
        $response = $this->httpClient->get('https://updates.drupal.org/psa.json', $options);
      }
      catch (TransferException $exception) {
        Error::logException($this->logger, $exception);
        $response = $this->httpClient->get('http://updates.drupal.org/psa.json', $options);
      }
    }
    return (string) $response->getBody();
  }

}
