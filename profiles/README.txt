Place downloaded and custom installation profiles in this directory to ensure
separation from Drupal core profiles and to facilitate safe, self-contained code
updates.

In multisite configuration, installation profiles found in this directory are
available to all sites during their initial site installation. Shared common
profiles may also be kept in the sites/all/profiles directory and will take
precedence over profiles in this directory. Alternatively, the
sites/your_site_name/profiles directory pattern may be used to restrict a
profile's availability to a specific site instance.

Additionally, modules and themes may be placed inside subdirectories in a
specific installation profile such as profiles/your_site_profile/modules and
profiles/your_site_profile/themes respectively to restrict their usage to only
sites that were installed with that specific profile.

Refer to the "Installation Profiles" section of the README.txt in the Drupal
root directory for further information.
