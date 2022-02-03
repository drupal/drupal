# Drupal Dev Environments

The fastest way to spin up Drupal development environments, powered by Lando.

## Deploying

### Gitpod

[![Open in Gitpod](https://gitpod.io/button/open-in-gitpod.svg)](https://gitpod.io/#https://github.com/lando/drupal-launcher)

### GitHub Codespaces

1. Make sure you [have Codespaces enabled](https://docs.github.com/en/codespaces/managing-codespaces-for-your-organization/enabling-codespaces-for-your-organization) for your user/org.
2. Click the green "Code" button in the GitHub interface above and create a new Codespaces environment.

### On Your Computer

1. Make sure you have [Lando installed.](https://docs.lando.dev/basics/installation.html)
2. Clone this repo.
3. Run `lando start` within the cloned directory.
4. After your application has started, run `lando composer require drush/drush && lando composer install`

## Using with an Existing Project

This repo is forked from the official Drupal upstream to give you the most up-to-date Drupal install possible. If you have an existing Drupal project and would like to deploy it using this configuration, copy the following files/directories to your install:

- .lando.yml
- .codespaces
- .gitpod.yml

If you aren't running Drupal 9, edit the `.lando.yml` file to reflect the appropriate version of Drupal.
