=== Smart Archives Reloaded ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: archive, post list
Requires at least: 2.8
Tested up to: 2.9-rare
Stable tag: trunk

An elegant and easy way to present your archives.

== Description ==

Smart Archives Reloaded is an enhanced version of the [Smart Archives](http://justinblanton.com/projects/smartarchives/) plugin by Justin Blanton.

Why is this plugin better?

* **Easy setup** - no code editing required
* **Better caching** - no need to wait when publishing a new post
* **Settings page** - elegantly choose how you want the archives displayed

See a live [example](http://scribu.net/arhiva) on my site.

== Installation ==

The plugin can be installed in 3 easy steps:

1. Unzip "Smart Archives Reloaded" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.
1. Create a new page or post and add `[smart_archives]` to the content.

Alternatively, you can add `<?php smart_archives(); ?>` anywhere in your theme, where you want the archives displayed.

== Frequently Asked Questions ==

= "Can't open cache file!" =
Go to your wp-content/uploads directory, create an empty file called "sar_cache.txt" and set it's permissions (chmod) to 757.

== Screenshots ==

1. The Settings Page

== Changelog ==

= 1.6.2 =
* added %comment_count% tag
* added Belarusian translation
* dropped support for WordPress older than 2.8

= 1.6.1 =
* bugfix

= 1.6 =
* added list format option with these tags:
	<ul>
		<li>%post_link%</li>
		<li>%author_link%</li>
		<li>%author%</li>
	</ul>
* [more info](http://scribu.net/wordpress/smart-archives-reloaded/sar-1-6.html)

= 1.5.2 =
* added two filters: smart_archives_title, smart_archives_exclude_categories

= 1.5.1 =
* fixed "Save Changes" button
* l10n: danish, italian, russian

= 1.5 =
* numeric month links
* l10n
* [more info](http://scribu.net/wordpress/smart-archives-reloaded/sar-1-5.html)

= 1.4 =
* more optimization
* [more info](http://scribu.net/wordpress/smart-archives-reloaded/sar-1-4.html)

= 1.3 =
* optimization & bugfixes
* [more info](http://scribu.net/wordpress/smart-archives-reloaded/sar-1-3.html)

= 1.2 =
* better HTML output
* [more info](http://scribu.net/wordpress/smart-archives-reloaded/sar-1-2.html)

= 1.1 =
* uses wp-cron

= 1.0 =
* initial release

