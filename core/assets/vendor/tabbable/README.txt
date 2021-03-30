Drupal core uses the UMD build of tabbable.
To create this build:
Navigate to the root directory of the tabbable library.

Ensure that dependencies have been installed:
```
yarn install
```

Build files for production:
```
yarn build
```

This will create an index.umd.min.js file in dist/ that can be used in Drupal.
