# Bundle

## About

Bundle is a Prestashop module for bundling a product with other optional products.

This module is currently used in production websites with Prestashop 1.6 and PHP 7+, but you may need to tweak some CSS and/or JS for your needs. The best way to make changes and still get updates is to create your own git branch and rebase/merge/cherry-pick new versions or specific commits.

## Installation

This module is best used with Composer managing your Prestashop project globally. This method follows best practices for managing external dependencies of a PHP project.

Create or edit `composer.json` in the Prestashop root directory:

```json
"repositories": [
  {
    "type": "git",
    "url": "https://github.com/creativewave/ps-cwbundle"
  },
  {
    "type": "git",
    "url": "https://github.com/creativewave/ps-objectmodel-extension"
  },
  {
    "type": "git",
    "url": "https://github.com/creativewave/ps-module-configuration"
  },
],
"require": {
  "creativewave/ps-cwmedia": "^1"
},

```

Then run `composer update`.

## Todo

* Improvement: handle bundled products combinations.
* Improvement: reduce the amount of data fetched for each bundled products.
