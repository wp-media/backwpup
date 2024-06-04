![Continuous Integration](https://github.com/wp-media/backwpup-pro/workflows/Continuous%20Integration/badge.svg)

# BackWPup - WordPress Backup Plugin
Schedule complete automatic backups of your WordPress installation. Decide which content will be stored (Dropbox, S3…). This is the free version

## Description
The **backup plugin** **[BackWPup Pro](http://backwpup.com/)** can be used to save your complete installation including /wp-content/ and push them to an external Backup Service, like **Dropbox**, **S3**, **FTP** and many more, see list below. With a single backup .zip file you are able to easily restore an installation.

BackWPup Free is the number 1 backup-plugin for WordPress with nearly 1.000.000 downloads and in the top 20 of all WordPress Plugins (checked on rankwp.com)

* Database Backup  *(needs mysqli)*
* WordPress XML Export
* Generate a file with installed plugins
* Optimize Database
* Check and repair Database
* File backup
* Backups in zip, tar, tar.gz format *(needs gz, ZipArchive)*
* Store backup to directory
* Store backup to FTP server *(needs ftp)*
* Store backup to Dropbox *(needs curl)*
* Store backup to S3 services *(needs curl)*
* Store backup to Microsoft Azure (Blob) *(needs curl)*
* Store backup to RackSpaceCloud *(needs PHP curl)*
* Store backup to SugarSync *(needs curl)*
* Store backup to Amazon Glacier *(needs PHP curl)*
* Store backup to Google Drive *(needs curl)*
* Store backup to OneDrive *(needs curl)*
* Store backup to HiDrive *(needs curl)*
* Send logs and backups by email
* Multi-site support only as network admin

**Remember: The most expensive backup is the one you never did! And please test your backups!**

**Made by [WP Media](https://wp-media.me) &middot; We love WordPress**

## Requirements
* PHP >= 7.2
* WordPress >=3.9
* To use the Plugin with full functionality PHP 7.2 with mysqli, FTP,gz, ZipArchive and curl is needed.
* Plugin functions that don't work because of your server settings, will not be displayed in admin area.

## Screenshots

1. [Working job and jobs overview](https://raw.github.com/inpsyde/backwpup/master/screenshot-1.png)
2. [Job creation/edit](https://raw.github.com/inpsyde/backwpup/master/screenshot-2.png)
3. [Displaying logs](https://raw.github.com/inpsyde/backwpup/master/screenshot-3.png)
4. [Manage backup archives](https://raw.github.com/inpsyde/backwpup/master/screenshot-4.png)
5. [Dashboard](https://raw.github.com/inpsyde/backwpup/master/screenshot-5.png)

## Development

### Install dependencies & build

- `$ composer install`
- `$ npm install`
- `$ sudo npm install --global gulp-cli`
- `$ gulp buildAssets`

### Unit tests and code style

1. `$ composer install`
2. `$ ./vendor/bin/phpunit`

### Building a release package

If you want to build a release package
(that can be used for deploying a new version on wordpress.org or manual installation on a WP website via ZIP uploading),
follow these steps:

1. Run the commands from "Install dependencies & build"
2. The following command should get you a Free version ZIP file ready to be used on a WordPress site:

```
$ gulp free --packageVersion=?.?.? --compressPath=.
```
or for crate the PRO Version Zip for the german shop:

```
$ gulp pro --packageVersion=?.?.? --compressPath=. --language=de
```
or for crate the PRO Version Zip for the english shop:

```
$ gulp pro --packageVersion=?.?.? --compressPath=. --language=en
```

## Setup

You can install BackWPuü locally using the dev environment of your preference, or you can use the DDEV setup provided in this repository which includes WP and all developments tools.

To set up the DDEV environment, follow these steps:

0. Install Docker and [DDEV](https://ddev.readthedocs.io/en/stable/).
1. Edit the configuration in the [`.ddev/config.yml`](.ddev/config.yaml) file if needed.
2. `$ ddev start`
3. `$ ddev orchestrate` to install WP.
4. Open https://backwpup-pro.ddev.site

Use `$ ddev orchestrate -f` for reinstalattion (will destroy all site data).
You may also need `$ ddev restart` to apply the config changes.

### Running tests and other tasks in the DDEV environment

For debugging, see [the DDEV docs](https://ddev.readthedocs.io/en/stable/users/step-debugging/).
Enable xdebug via `$ ddev xdebug`, and press `Start Listening for PHP Debug Connections` in PHPStorm.
After creating the server in the PHPStorm dialog, you need to set the local project path for the server plugin path.
It should look [like this](https://i.imgur.com/ofsF1Mc.png).


