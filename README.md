## Introduction

The Islandora Compound Object Solution Pack enables the creation and management
of parent-child relationships between objects, and an interface to navigate
between children of the same object. Children have an order within their parent,
which can be managed from the parent object.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/discoverygarden/islandora)

Specific features require the following modules/libraries:

* [Islandora Solr Search](https://github.com/discoverygarden/islandora_solr)
Module
    * Provides a Solr backend to retrieve children instead of using the resource
    index
    * Configurable ability to hide child objects from Solr search results

* [JAIL](https://github.com/sebarmeli/JAIL) JQuery library 
    * For the JAIL Display (lazy-loading) block

## Installation

Install as
[usual](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

If using the JAIL display, the [JAIL](https://github.com/sebarmeli/JAIL)
library must be present within sites/all/libraries/JAIL.

## Usage

This module provides a "Compound CModel". Objects of this type are shells to
hold children. They have no content of their own, and the object page at a
Compound CModel object displays the content and metadata of its first child.
This module can be configured to allow other objects to have children, in
which case, the parent object appears as usual, and the navigation block
displays the parent followed by its children.

Compound relationships are managed through the __Manage » Compound__ tab which
appears on all objects.

Navigation between objects linked by a Compound relationship requires a block to
be placed on the interface in __Structure » Blocks__. This module provides two
options: a standard Islandora Compound Object Navigation block, and the
Islandora Compound JAIL Display, which uses a javascript library for
lazy-loading (improving performance on compound objects with many children).

## Configuration

Options for this module can be set at Configuration > Islandora > Solution pack
configuration > Compound Object Solution Pack
(admin/config/islandora/solution_pack_config/compound_object).

![Configuration](https://user-images.githubusercontent.com/25011926/39889778-d1a91aca-5466-11e8-8eb1-1978cac81104.png)

### Theme:

The "Islandora Compound Object Navigation" block can be themed. See
`theme_islandora_compound_prev_next()`.

### Drush:

A Drush command has been added, to be run from the command line (Terminal),
that will update the existing rel-predicate of existing compound objects to
`isConstituentOf`. It can be run with the drush command
`drush update_rels_predicate`. This command accpets no arguments.

## Documentation

Further documentation for this module is available at
[our wiki](https://wiki.duraspace.org/display/ISLANDORA/Compound+Solution+Pack).

## Troubleshooting/Issues

Having problems or solved one? Create an issue, check out the Islandora Google
groups.

* [Users](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora)
* [Devs](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora-dev)

or contact [discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module, please check out the helpful
[Documentation](https://github.com/Islandora/islandora/wiki#wiki-documentation-for-developers),
[Developers](http://islandora.ca/developers) section on Islandora.ca and create
an issue, pull request and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
