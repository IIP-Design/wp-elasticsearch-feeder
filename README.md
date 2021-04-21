# WordPress Elasticsearch Feeder

This plugin extends the any WordPress site's REST API, adding an endpoint that can be used to ingest the site's content into an Elasticsearch index. By default, the plugin handles WordPress posts, but can be further extended for custom post types. It will normalize the post data into the format expected by the GPA Lab-managed Content Distribution Platform.

## Installation

### Basic

To install this plugin, you can copy the files into the plugins directory of your WordPress install. An easy way to do this is to clone the repository from GitHub:

```bash
$ cd my-site/wp-content/plugins
$ git clone https://github.com/IIP-Design/wp-es-feeder.git
```

### Composer

If using a Composer build process, add a reference to the plugin's git repository to the repositories array of your `composer.json`. In the require section, add an entry for `iip-design/wp-elasticsearch-feeder` pointing to the version of the plugin you would like to use. Your resulting `composer.json` file will look something like this:

```json
{
  "name": "sample-webroot",
  "repositories": [
    {
      "type": "git",
      "url": "git@github.com:IIP-Design/wp-es-feeder"
    },
    {
      "other repo": "..."
    }
  ],
  "require": {
    "iip-design/wp-elasticsearch-feeder": "*",
    "other-dependency": "..."
  }
}
```
