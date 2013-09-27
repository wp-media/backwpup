# BackWPup Free - WordPress Backup Plugin
Schedule complete automatic backups of your WordPress installation. Decide which content will be stored (Dropbox, S3…). This is the free version

## Description
The **backup plugin** **[BackWPup Free](http://marketpress.com/product/backwpup-pro/)** can be used to save your complete installation including /wp-content/ and push them to an external Backup Service, like **Dropbox**, **S3**, **FTP** and many more, see list below. With a single backup .zip file you are able to easily restore an installation. Please understand: this free version will not be supported as good as the [BackWPup Pro version](http://marketpress.com/product/backwpup-pro/).

BackWPup Free is the number 1 backup-plugin for WordPress with nearly 1.000.000 downloads and in the top 20 of all WordPress Plugins (checked on rankwp.com)

* Database Backup  *(needs mysqli)*
* WordPress XML Export
* Generate a file with installed plugins
* Optimize Database
* Check and repair Database
* File backup
* Backups in zip, tar, tar.gz, tar.bz2 format *(needs gz, bz2, ZipArchive)*
* Store backup to directory
* Store backup to FTP server *(needs ftp)*
* Store backup to Dropbox *(needs curl)*
* Store backup to S3 services *(needs curl)*
* Store backup to Microsoft Azure (Blob) *(needs PHP 5.3.2, curl)*
* Store backup to RackSpaceCloud *(needs PHP 5.3.2, curl)*
* Store backup to SugarSync *(needs curl)*
* Send logs and backups by email
* Multi-site support only as network admin
* Pro version and support available - [BackWPup Pro](http://marketpress.com/product/backwpup-pro/)


**Remember: The most expensive backup is the one you never did! And please test your backups!**

Get the [BackWPup Pro](http://marketpress.com/product/backwpup-pro/) Version with more features on [MarketPress.com](http://marketpress.com/product/backwpup-pro/)

**Made by [Inpsyde](http://inpsyde.com) &middot; We love WordPress**

Have a look at our other premium plugins at [MarketPress.com](http://marketpress.com).


## Available languages
* english (standard)
* french / français (fr_FR)
* german / deutsch (de_DE)
* russian / pоссия (ru_RU)
* simplified chinese (zh_CN)

## Requirements
* WordPress 3.2 and PHP 5.2.6 required!
* To use the Plugin with full functionality PHP 5.3.3 with mysqli, FTP,gz, bz2,  ZipArchive and curl is needed.
* Plugin functions that don't work because of your server settings, will not be displayed in admin area.


## Screenshots

1. [Working job and jobs overview](https://raw.github.com/inpsyde/backwpup/master/screenshot-1.png)
2. [Job creation/edit](https://raw.github.com/inpsyde/backwpup/master/screenshot-2.png)
3. [Displaying logs](https://raw.github.com/inpsyde/backwpup/master/screenshot-3.png)
4. [Manage backup archives](https://raw.github.com/inpsyde/backwpup/master/screenshot-4.png)
5. [Dashboard](https://raw.github.com/inpsyde/backwpup/master/screenshot-5.png)


## Changelog
### Version 3.0.14-beta
* Fixed: Message about aborted step did not display correctly
* Improved: Overall performance while generating backup archives
* Improved: Uploads of backup archives to FTP/S3/Dropbox/Azure can be continued
* Improved: Script re-starts based upon time while generating archives and uploading
* Improved: Reduced risk of running scripts being stopped via external processes in fcgi mode
* Improved: Backup destinations and their dependencies only being loaded when needed
* Improved: Required dependencies for destinations being displayed now
* Improved: Displaying of error messages as error messages (red, not yellow)
* Improved: Reduced size of vendor/SDK directory by 50%
* Updated: AWS SDK to Version 2.4.4 (PHP 5.3.3+)
* Updated: RSC SDK to Version 1.5.10
* Updated: SwiftMailer to Version 5.0.1
* PRO: Wizards using a separate session handling now
