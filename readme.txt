=== WP Custom Status Manager ===
Tags: wp, custom, post, type, status, statuses, cpt, manager
Requires at least: 6.0.1
Tested up to: 6.5.3
Requires PHP: 7.4 or later
Stable tag: 1.0.5
Contributors: giangel84
Donate link: https://www.paypal.com/donate?hosted_button_id=DEFQGNU2RNQ4Y
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Create your custom statuses for the core and the custom post type (CPT)

== Description ==

With this plugin you can create, edit and delete, your custom statuses for the WordPress post types.
Manage separately groups of status, for each CPT (Custom post type) you can add unlimited numbers of statuses.
It help admins to have more customized CPT areas for all the projects that need one ore more custom post type.
You can't create CPT, register them manually ore use another plugin like Toolset Types.
You can't modify the standard core statuses (Draft, Publish, Private, Future), but you can hide them just with a click!

= Localization =
* English (default) - always included
* Italian - always included
* .pot file ('hw-wp-status-manager.pot') for translators is also always included

== Installation ==

* Install the Plugin and Activate it.
* Go to WP Custom Status Manager settings page from the left menu.
* Click one post type from the list to add a new custom status for that CPT.
* Read the FAQs to understand how the plugin work.
* That's all! Enjoy.

== Donate ==
If you like this plugin and want to support my work, you can make a donation at this address: https://www.paypal.com/donate?hosted_button_id=DEFQGNU2RNQ4Y - Thank you very much!

== Frequently Asked Questions ==

= How it works? =

It's easy:
Imagine you have a CPT called "Invoices", you can create the status "Paid", "Not Paid" and "Canceled".
You can hide the standard core's statuses like "Draft", "Publish", "Private", to have only the custom statuses and a cleaner dropdown.
The plugin use the native "register_post_status" WP function to register your custom statuses.
It implement a way to show the statuses on the dropdown while editing the post, using jQuery, a feature that WP doesn't support yet natively with the "register_post_status" function.
Most part of plugin use Ajax calls, be sure jQuery is active on admin dashboard and that not any other plugin is breaking it.

= How to create a new status?

* Go to WP Custom Status Manager settings page from the left menu in admin dashboard.
* From the table's list, click on the post type name.
* A modal dialog windows will open.
* Just put the name of your new custom status and save it.

= How can I hide the standard core's statuses for a specific CPT?

From the CPT list, click "No" on the column "Hide core status", to switch the option to "Yes".
Go to your CPT and now you should see only custom statuses on the dropdown.

= Why the "Publish" button now say "Update" ?

Don't worry, this is a normal behaviour of the plugin:
Classic "Publish" button disappear for that specific post type, if you decide to hide its core statuses.

Full explanation with example:
When you push the "publish" button, WordPress set the new post status to "Published", regardless of the current status is: that's ok if you 're using the core statuses, and you really want to set the "Publish" status to the post, but since you decided to hide the core statuses it means that you don't really need the "Publish" status, right?
In this case, to avoid mistakes and habit actions, such as "Publish" button is intended as "save the post", the "Publish" button come hidden and another button is shown, by the way, it does the same things as "Save draft" button (that update the post without changing the status to another one).

= What happen if I delete a custom status?

Whether you delete a custom status, simply it will no longer registered as a WP recognisable status.
So all the posts previously sets with the deleted status, they still on the database, but you'll not be able to show them in the list of posts.
You can re-create the status as prevoius was, using exactly the same singular label, and magically all the posts will show again.

= Why I cannot modify the singular label of a status?

That's because once you create the status, the singular label is used by WP to generate the status-slug.
The status-slug is used to store the status of a post when you save or edit it.
So if you should edit the singular label of the status "paid" to "sold", you'll no longer able to show every post with the "paid" status.
So to avoid this action, you can't edit the singular label, just the plural.
Maybe in a future version of the plugin, will made a functional code which change all the post's statuses according to the edit done to the singular label.

= Can I test the plugin and then remove all data for a clean uninstall?

Yeah of course you can do it.
Use the button "I understand, reset all options and data" to delete all datas safety, and disable the plugin.
Then delete it manually from your WP Plugin's list.

== Screenshots ==

1. Get a clear and full list of your registered post types, included builtin and customs.
2. Create your new status
3. Have a look. One Click way to hide the default WP statuses (Draft, Publish, Private), if you need to clean the dropdown.
4. Set your custom statuses to your posts.

== Contributors & Developers ==
[Translate "WP Custom Status Manager" into your language](https://translate.wordpress.org/projects/wp-plugins/hw-wp-status-manager).

== Changelog ==

= 1.0.5 =
* Fix deprecated notice with PHP 8

= 1.0.4 =
* Fixed some warnings
* Conversion of db values from JSON to Serialized strings
* New admin menu dash-icon (Flag)

= 1.0.3 =

* Fixed table creation error on plugin activation

= 1.0.2 =

* Fixed warning error in hw_wp_status_manager_get_custom_statuses() function while it return a not-array value
* Changed Plugin's name to help users to find it easier, in wp repository

= 1.0.1 =

* Fixed static boolean values in Register status
* Translation fix

= 1.0.0 =

* First stable release