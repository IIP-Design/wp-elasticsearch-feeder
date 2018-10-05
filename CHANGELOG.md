# Change Log
##### All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.3.2

**Features Added:**

- Updated default thumbnail object when thumbnail is missing.

## 2.3.1

**Features Added:**

- Updates to pull in `name`, `alt`, `caption`, and `longdesc` properties for thumbnail object
- Moves thumbnail size properties in to a `sizes` object

## [2.3.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/tree/2.3.0) (2018-07-18)

**Features Added:**

- Removed custom_taxonomies and replaced with site_taxonomies.
- Removed the merging of categories and tags.
- Put tags and categories (appropriately named from post_tag and category) into site_taxonomies.
- Switched the taxonomies property to custom_taxonomies and modified the schema a bit to conform with CDP API.

## [2.2.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/tree/2.2.0) (2018-06-08)

**Features Added:**

- Created new sidebar metabox for the language dropdown.
- Added checkbox to settings page for enabling language dropdown on post post type (only).
- Updated REST data to use language dropdown value if enabled.

## [2.1.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/tree/2.1.0) (2018-05-29)

**Features Added:**

- Speed up the re-sync initiation process
- Fire PUT updates for each translation of a multi-lingual post

## [2.0.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/tree/2.0.0) (2018-05-02)

**Features Added:**

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
- Rebranded ES Feeder.
- Updated error notification with better verbiage.
- Cleared error notification after fix errors button is clicked.
- Fixed error occuring when post is restored from the trash.
- Immediately set sync status to ERROR if one is detected on the initial request.
- Fixed WP Video file being deleted when marked "Save as draft".
- Added category taxonomy integration.
- Allowed post types based on plugins instead of API.
- Removed replacement of dashes with periods in UUIDs.
- Guzzle URL Initialization.
- Create sync status buttons - Reduce dot size to 10px.
- Page edit sync status updates during indexing.

## [1.0.0](https://github.com/IIP-Design/wp-elasticsearch-feeder/tree/1.0.0) (2017-12-20)
**Features Added:**

- Added correct timestamps for ES.
