=== Cron Jobs ===
Contributors: arielhr1987, jlcd0894  
Donate link: https://github.com/arielhr1987  
Tags: cron, scheduler, wp-cron, automation, admin tools  
Requires at least: 4.1  
Tested up to: 6.8  
Stable tag: 1.2.11  
Requires PHP: 5.4  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Easily manage and monitor your WordPress cron jobs from a clean, intuitive interface.

== Description ==

**Cron Jobs** is a lightweight yet powerful plugin that simplifies the management of your WordPress cron events.

With this tool, you can quickly view, run, and modify your scheduled tasks (cron jobs) without writing code. It's ideal for developers, site managers, or anyone needing better control over WordPress's background processes.

Key features include:

* Run or bulk run selected cron jobs instantly.
* Edit cron job schedule, next run time, and arguments directly from the list view.
* Customize visible columns and preferences via the native "Screen Options" panel.
* Access help and documentation to better understand how WordPress cron works.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/leira-cron-jobs`, or install it directly via the WordPress Plugin Directory.
2. Activate it from the **Plugins** menu in your WordPress admin.
3. Navigate to **Tools → Cron Jobs** to start managing your tasks.
4. That’s it — happy automating!

== Frequently Asked Questions ==

= Can I create new cron jobs with this plugin? =  
Not at the moment. Cron Jobs allows you to view, edit, run, and delete existing cron jobs, but not create new ones (yet).

= What parts of a cron job can I edit? =  
You can modify the schedule, next run time, and the arguments passed to the cron hook.

= Can I manage or create custom schedules? =  
Currently, no. Managing schedules is not yet supported, but it's a planned feature for a future release.

== Screenshots ==
1. Access the "Cron Jobs" screen from the "Tools" menu.
2. View a list of all registered cron jobs. Jobs without actions appear highlighted in red.
3. Quickly edit arguments, schedule, and next run time.
4. Perform bulk actions on selected cron jobs.
5. Customize the table view via the "Screen Options" menu.
6. Helpful overview about WordPress cron behavior.
7. Detailed "Screen Options" explanation.
8. Display of WordPress constant statuses related to cron.
9. Use the search bar to filter cron jobs.
10. Delete only jobs with missing hooks to prevent breaking functionality.

== Changelog ==

= 1.2.11 =
* Improved the plugin's compatibility with WordPress 6.8
* New method to handle running cron jobs, ensuring better reliability

= 1.2.10 =
* Verified compatibility with WordPress 6.6
* Fixed security issue reported by Wordfence
* Improved input sanitization

= 1.2.9 =
* Verified compatibility with WordPress 5.9

= 1.2.8 =
* Verified compatibility with WordPress 5.7

= 1.2.7 =
* Verified compatibility with WordPress 5.6
* Fixed a bug when editing cron job arguments

= 1.2.6 =
* Added GitHub Actions for automated deployment
* Updated GitHub Actions for syncing readme/assets
* Bug fix (thanks to ptibogxiv)

= 1.2.5 =
* Minor bug fixes

= 1.2.4 =
* Minor bug fixes

= 1.2.3 =
* Added "Rate us" link in footer
* Improved notification handling using cookies
* Fixed typos

= 1.2.2 =
* Added Spanish language support
* Expanded Help tab with more cron job info

= 1.2.1 =
* Added legend for blue/red table rows in the Help tab
* Fixed date/time editor styling

= 1.2.0 =
* Major plugin refactor for improved performance and maintainability

= 1.1.2 =
* Improved table sorting functionality

= 1.1.1 =
* Updated screenshot 6
* Source code cleanup

= 1.0.0 =
* Initial release
