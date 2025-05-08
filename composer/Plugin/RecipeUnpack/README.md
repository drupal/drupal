# Drupal Recipe Unpack Plugin

Thanks for using this Drupal component.

You can participate in its development on Drupal.org, through our issue system:
https://www.drupal.org/project/issues/drupal

You can get the full Drupal repo here:
https://www.drupal.org/project/drupal/git-instructions

You can browse the full Drupal repo here:
https://git.drupalcode.org/project/drupal

## Overview

The Recipe Unpacking system is a Composer plugin that manages "drupal-recipe"
packages. Recipes are special Composer packages designed to bootstrap Drupal
projects with necessary dependencies. When a recipe is installed, this plugin
"unpacks" it by moving the recipe's dependencies directly into your project's
root `composer.json`, and removes the recipe as a project dependency.

## Key Concepts

### What is a Recipe?

A recipe is a Composer package with type `drupal-recipe` that contains a curated
set of dependencies, configuration and content but no code of its own. Recipes
are meant to be "unpacked" and "applied" rather than remain as runtime
dependencies.

### What is Unpacking?

Unpacking is the process where:

1. A recipe's dependencies are added to your project's root `composer.json`
2. The recipe itself is removed from your dependencies
3. The `composer.lock` and vendor installation files are updated accordingly
4. The recipe will remain in the project's recipes folder so it can be applied

## Commands

### `drupal:recipe-unpack`

Unpack a recipe package that's already required in your project.

```bash
composer drupal:recipe-unpack drupal/example_recipe
```

Unpack all recipes that are required in your project.

```bash
composer drupal:recipe-unpack
```

#### Options

This command doesn't take additional options.

## Automatic Unpacking

### After `composer require`

By default, recipes are automatically unpacked after running `composer require`
for a recipe package:

```bash
composer require drupal/example_recipe
```

This will:
1. Download the recipe and its dependencies
2. Add the recipe's dependencies to your project's root `composer.json`
3. Remove the recipe itself from your dependencies
4. Update your `composer.lock` file

### After `composer create-project`

Recipes are always automatically unpacked when creating a new project from a
template that requires this plugin:

```bash
composer create-project drupal/recommended-project my-project
```

Any recipes included in the project template will be unpacked during
installation, as long as the plugin is enabled.

## Configuration

Configuration options are set in the `extra` section of your `composer.json`
file:

```json
{
  "extra": {
    "drupal-recipe-unpack": {
      "ignore": ["drupal/recipe_to_ignore"],
      "on-require": true
    }
  }
}
```

### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `ignore` | array | `[]` | List of recipe packages to exclude from unpacking |
| `on-require` | boolean | `true` | Automatically unpack recipes when required by `composer require` |

## How Recipe Unpacking Works

1. The system identifies packages of type `drupal-recipe` during installation
2. For each recipe not in the ignore list, it:
   - Extracts its dependencies
   - Adds them to the root `composer.json`
   - Recursively processes any dependencies that are also recipes
   - Removes the recipe and any dependencies that are also recipes from the root
     `composer.json`
3. Updates all necessary Composer files:
   - `composer.json`
   - `composer.lock`
   - `vendor/composer/installed.json`
   - `vendor/composer/installed.php`

## Cases Where Recipes Will Not Be Unpacked

Recipes will **not** be unpacked in the following scenarios:

1. **Explicit Ignore List**: If the recipe is listed in the `ignore` array in
   your `extra.drupal-recipe-unpack` configuration
   ```json
   {
     "extra": {
       "drupal-recipe-unpack": {
         "ignore": ["drupal/recipe_name"]
       }
     }
   }
   ```

2. **Disabled Automatic Unpacking**: If `on-require` is set to `false` in your
   `extra.drupal-recipe-unpack` configuration
   ```json
   {
     "extra": {
       "drupal-recipe-unpack": {
         "on-require": false
       }
     }
   }
   ```

3. **Development Dependencies**: Recipes in the `require-dev` section are not
   automatically unpacked
   ```json
   {
     "require-dev": {
       "drupal/dev_recipe": "^1.0"
     }
   }
   ```
   You will need to manually unpack these using the `drupal:recipe-unpack`
   command if desired.

4. **With `--no-install` Option**: When using `composer require` with the
   `--no-install` flag
   ```bash
   composer require drupal/example_recipe --no-install
   ```
   In this case, you'll need to run `composer install` afterward and then
   manually unpack using the `drupal:recipe-unpack` command.

## Example Usage Scenarios

### Basic Recipe Installation

```bash
# This will automatically install and unpack the recipe
composer require drupal/example_recipe
```

The result:
- Dependencies from `drupal/example_recipe` are added to your root
  `composer.json`
- `drupal/example_recipe` itself is removed from your dependencies
- You'll see a message: "drupal/example_recipe unpacked successfully."
- The recipe files will be present in the drupal-recipe installer path

### Manual Recipe Unpacking

```bash
# First require the recipe without unpacking
composer require drupal/example_recipe --no-install
composer install

# Then manually unpack it
composer drupal:recipe-unpack drupal/example_recipe
```

### Working with Dev Recipes

```bash
# This won't automatically unpack (dev dependencies aren't auto-unpacked)
composer require --dev drupal/dev_recipe

# You'll need to manually unpack if desired (with confirmation prompt)
composer drupal:recipe-unpack drupal/dev_recipe
```

### Creating a New Project with Recipes

```bash
composer create-project drupal/recipe-based-project my-project
```

Any recipes included in the project template will be automatically unpacked
during installation.

## Best Practices

1. **Review Recipe Contents**: Before requiring a recipe, review its
   dependencies to understand what will be added to your project.

2. **Consider Versioning**: When a recipe is unpacked, its version constraints
   for dependencies are merged with your existing constraints, which may result
   in complex version requirements.

3. **Dev Dependencies**: Be cautious when unpacking development recipes, as
   their dependencies will be moved to the main `require` section, not
   `require-dev`.

4. **Custom Recipes**: When creating custom recipes, ensure they have the
   correct package type `drupal-recipe` and include appropriate dependencies.

## Troubleshooting

### Recipe Not Unpacking

- Check if the package type is `drupal-recipe`
- Verify it's not in your ignore list
- Confirm it's not in `require-dev` (which requires manual unpacking)
- Ensure you haven't used the `--no-install` flag without following up with
  installation and manual unpacking

### Unpacking Errors

If you encounter issues during unpacking:

1. Check Composer's error output for specific issues and run commands with the
   `--verbose` flag
2. Verify that version constraints between your existing dependencies and the
   recipe's dependencies are compatible
3. For manual troubleshooting, consider temporarily setting `on-require` to
   `false` and unpacking recipes one by one
