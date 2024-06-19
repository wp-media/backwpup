=== BackWPup – WordPress Backup Plugin | Easy Backup & Restore ===
Contributors: backwpup, wp_rocket, imagify
Tags: backup, restore, cloud backup, database backup, wordpress backup
Requires at least: 3.9
Tested up to: 6.5.3
Requires PHP: 7.2
Stable tag: 4.1.1
License: GPLv2+

Create a complete WordPress backup easily. Schedule automatic backups, store securely, and restore effortlessly with the best WordPress backup plugin!

== Description ==

= The Best WordPress Backup & Restore Plugin =

[BackWPup](https://backwpup.com/) is the most comprehensive backup & restore plugin for WordPress.

Easily create a complete WordPress backup, store it on external services (such as **Dropbox**, **S3**, **FTP**, and more) and restore your backup directly from your WordPress admin, in just a few clicks.

BackWPup is designed for ease of use. Even beginners can create a reliable backup of their WordPress sites with just a few clicks. With BackWPup, you can enjoy peace of mind knowing your data is safe and secure. Whether you are a small business owner or a large enterprise, BackWPup is the tool you need to protect your WordPress site.

= Schedule and Manage Backups Easily =

With BackWPup, you have full control over your backup process. You can back up your entire WordPress site, including files and database, save them to multiple locations, and easily restore your site from a backup if anything goes wrong.

You can choose what to back up, how often to perform backups, and where to store them:
* Backup your entire WordPress installation, including the /wp-content/ folder and your database.
* Schedule automatic backups to run daily, weekly, or monthly, ensuring that your data is always up-to-date and secure.
* Store your backup in different locations: Dropbox, S3, FTP, Google Drive, OneDrive, and more, ensuring your data is always secure and accessible.

This flexibility makes BackWPup the best choice for WordPress backup.

= Easily Restore Your WordPress Site =

The restore option is now included in the free version. Easily restore your site from a backup with just a few clicks.
To restore a backup, go to the BackWPup plugin dashboard in your WordPress admin area. Navigate to the 'Backups' tab to see a list of your saved backups. Select the backup you wish to restore and click the 'Restore' button. Follow the on-screen instructions to complete the restoration process.
This feature ensures that even in the event of data loss or site issues, you can quickly and efficiently restore your site to its previous state.

= Improve Your Site’s Reliability and Performance  =
Did you know that regular backups and database maintenance can improve the performance and reliability of your WordPress site? With BackWPup, you can ensure that your data is always protected and that your site is always running smoothly.
By scheduling regular backups and database maintenance, you can avoid data loss and downtime. BackWPup makes it easy to protect your data and keep your site running at its best.
Even Google recommends regular backups and database maintenance to ensure the reliability and performance of your site. With BackWPup, you can follow best practices and keep your site secure and reliable.


= What Do Our Users Think Of BackWPup? =

Here’s what our users have to say about us after using BackWPup:

>"Thanks to the developers for a very handy plugin! I’ve been using it for many years and it has never let me down! Thank you!" — [alexeytrusovru ](https://wordpress.org/support/topic/very-convenient-and-easy-to-configure-plug-ins/)>

>”Must have for backup. So easy to use and so much feature. You can choose what to backup : files, db, plugin, theme… Then you can choose where to backup like upload to your dropbox.”— [zuriiwest](https://wordpress.org/support/topic/must-have-for-backup/)>

> "Using this for 2 years for 30 sites without any issue, worked perfectly for me!" — [hoathuy](https://wordpress.org/support/topic/using-this-for-2-years-for-30-sites-without-any-issue-worked-perfectly-for-me/)>

> "My favorite backup plugin – use it on many sites. I manage many sites, and this has been my favorite backup plugin for years. It has a number of features that are not available in the free versions of other backup plugins (or at least, not all in the same plugin)
" — [syzygist](https://wordpress.org/support/topic/my-favorite-backup-plugin-use-it-on-many-sites/)>


= Is BackWPup Free? =

You can use BackWPup for free with all its basic features. The free version includes complete backup, scheduling, support for external storage services (like Dropbox, S3, FTP, and more) and restore.

The pro version offers many additional features including more settings, destinations and of course access to our premium support. Check out our premium plans: [https://backwpup.com/#buy](https://backwpup.com/#buy)


= Who Are We? =

We are [WP Media](https://wp-media.me/), the company behind WP Rocket, the best caching plugin for WordPress.

Our mission is to improve the web. We are making it faster with [WP Rocket](https://wp-rocket.me/), lighter with [Imagify](https://imagify.io) and safer with [BackWPup](https://backwpup.com/).


= Get In Touch! =

* Website: [backwpup.com](https://backwpup.com/)
* Contact Us: [https://backwpup.com/contact/](https://backwpup.com/contact/)


= Related Plugins =

* [WP Rocket: The best performance plugin](https://wp-rocket.me/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=backwpupplugin) to speed up your WordPress website.
* [Imagify: The best image optimization plugin](https://imagify.io/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=backwpupplugin) to speed up your website with lighter images.
* [Lazy Load](https://wordpress.org/plugins/rocket-lazy-load/): The best Lazy Load script to reduce the number of HTTP requests and improve the website's loading time.
* [Heartbeat Control by WP Rocket](https://wordpress.org/plugins/heartbeat-control/): Heartbeat Control by WP Rocket: The best plugin to control the WordPress Heartbeat API and reduce CPU usage.
* [RocketCDN: The best CDN plugin for WordPress](https://rocketcdn.me/wordpress/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=backwpupplugin) to propel your content at the speed of light – no matter where your users are located in the world.
* [Increase Max upload file size](https://wordpress.org/plugins/upload-max-file-size/): The best plugin to increase the upload file size limit to any value with one click.



License: GPLv2 or later

License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Installation ==


= WordPress Admin Method =


1. Go to you administration area in WordPress `Plugins > Add`

2. Look for `BackWPup` (use search form)

3. Click on Install and activate the plugin


= FTP Method =

1. Upload the complete `backwpup` folder to the `/wp-content/plugins/` directory

2. Activate the plugin through the 'Plugins' menu in WordPress

= Pro Version =
[You can find a detailed tutorial in the BackWPup documentation]. (https://backwpup.com/docs/install-backwpup-pro-activate-licence/)



== Frequently Asked Questions ==

= How do I restore a backup? =
Restoring backups made with BackWPup can be done multiple ways. Please take a look at our [documentation here](https://backwpup.com/docs/how-to-restore-a-wordpress-backup/) to learn more.

= My backup jobs don’t seem to run as scheduled. =
BackWPup uses WordPress’ own cron job system (**WP Cron**) to execute scheduled backup jobs. In order for WordPress to “know” when to execute a job, its “inner clock” needs to be set regularly. That happens whenever someone (including yourself) visits your site.
If your site happens to not being visited for a period of time, WordPress’ inner clock gets sort of slow. In that case it takes an extra server-side cron job to regularly call http://your-site.tld/wp-cron.php and tell WordPress what time it is.

A simple way to find out whether WP Cron works as it should on your site is to create a new post and set its publishing date to some point in the future, i.e. 10 minutes from now. Then leave your site (that’s important), come back after 11 minutes and check whether your scheduled post has been published. If not, you’re very likely to have an issue with WP Cron.

= Yuk! It says: “ERROR: No destination correctly defined for backup!” =

That means a backup job has started, but BackWPup doesn’t know where to store the backup files. Please cancel the running job and re-edit its configuration. There should be a Tab “To: …” in your backup job’s configuration. Have you set a backup target correctly?

= A backup job has started, but nothing seems to be happening—not even when I restart it manually. =

**Solution #1**

* Open BackWPup->Settings
* Go to the Informations tab.
* Find *Server self connect:* in the left column.
* If it says something like *(401) Authorisation required* in the right column, go to the Network tab and set the username and password for server-side authentication.
* Try again starting the backup job.

**Solution #2**

* Open wp-config.php and find the line where it says `if ( !defined('ABSPATH') )`.
* Somewhere before that line add this: `define( 'ALTERNATE_WP_CRON', true );`

**Solution #3**

Not really a solution, but a way to identify the real problem: see remarks on WP Cron at the top.

= I get this error message: `The HTTP response test get a error "Connection time-out"` =
BackWPup performs a simple HTTP request to the server itself every time you click `run now` or whenever a backup job starts automatically. The HTTP response test message could mean:
* Your host does not allow *loop back connections*. (If you know what `WP_ALTERNATE_CRON` is, try it.)
* Your WordPress root directory or backup directory requires authentication. Set username and password in Settings->Network.
* The Server can’t resolve its own hostname.
* A plugin or theme is blocking the request.
* Other issues related to your individual server and/or WordPress configuration.

= I get a fatal error: `Can not create folder: […]/wp-content/backwpup-[…]-logs in […]/wp-content/plugins/backwpup/inc/class-job.php …` =
Please set CHMOD 775 on the /wp-content/ directory and refresh the BackWPup dashboard. If that doesn’t help, try CHMOD 777. You can revert it to 755 once BackWPup has created its folder.

= When I edit a job the Files tab loads forever. =
Go to Settings->General and disable “Display folder sizes on files tab if job edited”. Calculating folder sizes can take a while on sites with many folders.

= I generated a list of my installed plugins, but it’s hard to read. =
Try opening the text file in an editor software like Notepad++ (Windows) or TextMate (Mac) to preserve line-breaks.

= My web host notified me BackWPup was causing an inacceptable server load! =
Go to Settings->Jobs and try a different option for “Reduce server load”.

= Can I cancel a running backup job via FTP? =
Yes. Go to your BackWPup temp directory and find a file named `backwpup-xyz-working.json` where “xyz” is a random string of numbers and characters. Delete that file to cancel the currently running backup job.

= Can I move the temp directory to a different location? =
Yes. You need to have writing access to the wp-config.php file (usually residing in the root directory of your WordPress installation).

* Open wp-config.php and find the line where it says `if ( !defined('ABSPATH') )`.
* Somewhere *before* that line add this: `define( 'WP_TEMP_DIR', '/absolute/path/to/wp/your/temp-dir' );`
* Replace `/absolute/path/to/wp/` with the absolute path of your WordPress installation and `your/temp-dir` with the path to your new temp directory.
* Save the file.

= What do those placeholders in file names stand for? =
* %d = Two digit day of the month, with leading zeros
* %j = Day of the month, without leading zeros
* %m = Day of the month, with leading zeros
* %n = Representation of the month (without leading zeros)
* %Y = Four digit representation for the year
* %y = Two digit representation of the year
* %a = Lowercase ante meridiem (am) and post meridiem (pm)
* %A = Uppercase ante meridiem (AM) and post meridiem (PM)
* %B = [Swatch Internet Time](http://en.wikipedia.org/wiki/Swatch_Internet_Time)
* %g = Hour in 12-hour format, without leading zeros
* %G = Hour in 24-hour format, without leading zeros
* %h = Hour in 12-hour format, with leading zeros
* %H = Hour in 24-hour format, with leading zeros
* %i = Two digit representation of the minute
* %s = Two digit representation of the second
