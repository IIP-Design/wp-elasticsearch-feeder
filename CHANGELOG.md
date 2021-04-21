## Change Log

**All notable changes to this project will be documented in this file.**

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

### [Unreleased](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/2.5.0...HEAD)

#### :warning: Breaking:

- Rename the principal plugin class to `ES_Feeder`
- Change expected class name format for API custom post type extensions from `WP_ES_FEEDER_EXT_{TYPE}_Controller` to `ES_Feeder_REST_{TYPE}_Controller`

#### Added:

- Autoloader function to allow for easier use of class definitions without requiring manually inclusion the files
- Documentation comments for all functions and classes
- PHP and JavaScript linting using PHP CodeSniffer and ESLint, respectively

#### Changed:

- Register styles prior to enqueueing them
- Moved assorted function from the the plugin's principal class into a series of helper classes scoped to a specific area of responsibility
- Namespace all classes using `ES_Feeder`
- Update all dependencies and remove `composer.lock` file from `.gitignore`
- Break REST callback API endpoint into it's own REST controller

#### Fix:

- Correct the URL to the plugin's settings page in the plugin action links

### [2.5.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/2.4.1...2.5.0) (2019-05-29)

#### Added:

- Added the ability to use an owner dropdown on posts

### [2.4.1](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/2.4.0...2.4.1) (2019-01-30)

#### Added:

- Deleted all options created and used by the plugin in the uninstall file.

### [2.4.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/2.3.2...2.4.0) (2018-10-31)

#### Added:

- Created log section.
- Removed title property from sizes object.

### [2.3.2](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/2.3.1...2.3.2) (2018-10-05)

#### Added:

- Updated default thumbnail object when thumbnail is missing.

### [2.3.1](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/2.3.0...2.3.1) (2018-10-05)

#### Added:

- Updates to pull in `name`, `alt`, `caption`, and `longdesc` properties for thumbnail object
- Moves thumbnail size properties in to a `sizes` object

### [2.3.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/2.2.0...2.3.0) (2018-07-25)

#### Added:

- Removed custom_taxonomies and replaced with site_taxonomies.
- Removed the merging of categories and tags.
- Put tags and categories (appropriately named from post_tag and category) into site_taxonomies.
- Switched the taxonomies property to custom_taxonomies and modified the schema a bit to conform with CDP API.

### [2.2.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/2.1.0...2.2.0) (2018-06-08)

#### Added:

- Created new sidebar metabox for the language dropdown.
- Added checkbox to settings page for enabling language dropdown on post post type (only).
- Updated REST data to use language dropdown value if enabled.

### [2.1.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/2.0.0...2.1.0) (2018-05-29)

#### Added:

- Speed up the re-sync initiation process
- Fire PUT updates for each translation of a multi-lingual post

### [2.0.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/compare/1.0.0...2.0.0) (2018-05-02)

#### Added:

- Created sync validation button on settings page
- Improved re-sync process by syncing multiple in one AJAX call
- Updated languages property to match schema for translated posts
- Updated REST properties for post
- Fixed taxonomy not appearing until after a video is published.
- Only show CDP Taxonomy Term meta box when WP Categories unsupported
- Existing WordPress tags and categories are shuffled into tags property.
- Allowed featured image for use as thumbnail image.
- Updated plugins to use languages from API.
- Created live status box for settings page.
- Re-branded ES Feeder.
- Updated error notification with better verbiage.
- Cleared error notification after fix errors button is clicked.
- Fixed error occurring when post is restored from the trash.
- Immediately set sync status to ERROR if one is detected on the initial request.
- Fixed WP Video file being deleted when marked "Save as draft".
- Added category taxonomy integration.
- Allowed post types based on plugins instead of API.
- Removed replacement of dashes with periods in UUIDs.
- Guzzle URL Initialization.
- Create sync status buttons - Reduce dot size to 10px.
- Page edit sync status updates during indexing.

### [1.0.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/releases/tag/1.0.0) (2017-12-20 - Initial Release)

#### Added:

- Added correct timestamps for ES.
