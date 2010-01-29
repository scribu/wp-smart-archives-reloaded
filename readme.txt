=== Smart Archives Reloaded ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: archive, post list
Requires at least: 2.8
Tested up to: 2.9.1
Stable tag: 1.9.1

An elegant and easy way to present your archives.

== Description ==

Smart Archives Reloaded is an enhanced version of the [Smart Archives](http://justinblanton.com/projects/smartarchives/) plugin by Justin Blanton.

Why is this plugin better?

* **Easy setup** - no code editing required
* **Better caching** - no need to wait when publishing a new post
* **Settings page** - elegantly choose how you want the archives displayed
* **More options** - besides the 'block' and the 'list', you also have the 'fancy' and the 'menu' formats

Examples: [block & list](http://scribu.net/arhiva) | [fancy](http://www.conceptfusion.co.nz/archive)

**Translations:**

* Belarusian - [ilyuha](http://antsar.info/)
* Chinese - [Yorick Chen](http://www.pihai.net/technology/smart-archives-reloaded-chinese-translation.html)
* Danish - jos
* Dutch - [Lourens Rolograaf](http://rolograaf.nl)
* French - [Référenceur Freelance](http://www.referenceurfreelance.com), [Li-An](http://www.li-an.fr)
* German - [Cornelius Schiffer](http://schiffr.de/)
* Italian - [Gianni Diurno](http://gidibao.net/)
* Russian - [Fat Cow](http://www.fatcow.com)
* Uzbek - [Alexandra Bolshova](http://comfi.com)

== Installation ==

The plugin can be installed in 3 easy steps:

1. Unzip "Smart Archives Reloaded" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.
1. Create a new page or post and add `[smart_archives]` to the content.

Alternatively, you can add `<?php smart_archives(); ?>` anywhere in your theme, where you want the archives displayed.

== Frequently Asked Questions ==

= "Parse error: syntax error, unexpected T_CLASS..." Help! =

Make sure your new host is running PHP 5. Add this line to wp-config.php:

`var_dump(PHP_VERSION);`

= Fancy archive not working =

In footer.php in your theme directory, make sure you have this code somewhere:

`<?php wp_footer(); ?>`.

= How can I change the CSS or HTML? =

Read this: [Advanced Tweaking](http://scribu.net/wordpress/smart-archives-reloaded/advanced-tweaking.html)

== Screenshots ==

1. A fancy archive
2. The Settings Page

== Changelog ==

= 2.0 =
* added %excerpt% tag
* fewer queries
* more flexible generator class

= 1.9.1 =
* updated .pot file
* made generator class non-static

= 1.9 =
* added 'menu' format
* added new arguments: 'month_format', 'posts_per_month', 'generator'
* added 'smart_archives_css' filter
* [more info](http://scribu.net/wordpress/smart-archives-reloaded/sar-1-9.html)

= 1.8.3 =
* fancy archive fix for IE
* optimized CSS loading
* update French translation

= 1.8.2 =
* fix broken update

= 1.8.1 =
* added Clear cache button
* load js only when needed
* compatibility with the MailPress plugin

= 1.8 =
* override arguments with smart_archives() or [smart_archives]
* added include_cat arg
* added Chinese l10n
* [more info](http://scribu.net/wordpress/smart-archives-reloaded/sar-1-8.html)

= 1.7.1 =
* fancy archive improvements

= 1.7 =
* added "fancy" option
* added %category_link%, %category% and %date%
* added Uzbek translation
* [more info](http://scribu.net/wordpress/smart-archives-reloaded/sar-1-7.html)

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

