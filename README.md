## Introduction

The Islandora Compound Object Solution Pack enables generic parent-child
relationships between objects. The object view of a compound object is replaced
by the view of its first child object. The included "Islandora Compound Object
Navigation" block provides a thumbnail navigation of an object's siblings. A
"Compound" management tab allows for the addition and removal of parent and
child objects for each object.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/discoverygarden/islandora)

This module has the following as optional requirements for certain features:

For Islandora Compound Object JAIL Display:

* [JAIL](https://github.com/sebarmeli/JAIL) library 

## Installation

Install as
[usual](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

If utilizing the lazy loading image ability of the solution pack, the
[JAIL](https://github.com/sebarmeli/JAIL)
library must be present within sites/all/libraries/JAIL.

## Configuration

Set the 'Child relationship predicate' and 'Solr filter query', as well as
select options in Configuration > Islandora > Solution pack configuration >
Compound Object Solution Pack (admin/config/islandora/solution_pack_config/
compound_object).

Optionally, enable the JAIL compound block to utilize the lazy loading image
ability as outlined below.

![Configuration](https://user-images.githubusercontent.com/25011926/39889778-d1a91aca-5466-11e8-8eb1-1978cac81104.png)

### Block:

There exist two block options for displaying compound objects within Islandora.
The default "Islandora Compound Object Navigation" block will provide navigation
controls and loading of all objects related to the parent compound. The latter
option is a block utilizing the [JAIL](https://github.com/sebarmeli/JAIL)
library which allows for lazy loading of images. This allows the block to load
images only when they are being accessed which will greatly increase performance
on compounds with many children.

![compobjblocks_to_configure01b](https://cloud.githubusercontent.com/assets/11573234/24410256/9e01dfc0-13a0-11e7-9edf-454addc13128.JPG)



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
