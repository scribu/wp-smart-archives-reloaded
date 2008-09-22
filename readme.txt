=== Smart Archives Reloaded ===
Contributors: scribu
Donate link: http://scribu.net/projects
Tags: custom-fields, images, thumbs
Requires at least: 2.3
Tested up to: 2.6+
Stable tag: 1.2

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
1. Create a new page or post with `[smart_archives]` in it.

Alternatively, you can add `<?php smart_archives(); ?>` anywhere in your theme, where you want the archives displayed.

== Frequently Asked Questions ==

= "Can't open cache file!" =
Go to your plugin directory, create an empty file called "cache.txt" and change it's permissions (chmod) to 757.
