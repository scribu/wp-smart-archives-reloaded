=== Smart Archives Reloaded ===
Contributors: scribu
Tags: archive, archives, post list
Requires at least: 3.2
Tested up to: 3.4
Stable tag: 2.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily display posts grouped by year and month, in one or more elegant formats

== Description ==

Smart Archives Reloaded allows you to display a list of posts grouped by year and month. It also provides several interesting navigation elements.

**Format list:**

* **list** = a list of posts grouped by month
* **block** = a compact block of links to year and date archive pages
* **menu** = a two-level menu with links to year and date archive pages
* **both** = block + list ([example](http://scribu.net/archive))
* **fancy** = menu + list + JavaScript ([example](http://www.conceptfusion.co.nz/archive))

**No support**

I do not offer support for this plugin, either free or paid.

**Credits:**

* [Justin Blanton](http://justinblanton.com), for the original [Smart Archives](http://justinblanton.com/projects/smartarchives/) plugin
* [Simon Pritchard](http://www.conceptfusion.co.nz/), for the fancy format

Links: [Plugin News](http://scribu.net/wordpress/smart-archives-reloaded) | [Author's Site](http://scribu.net)

== Installation ==

See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

After activating it, create a new page or post and add `[smart_archives]` to the content.

Alternatively, you can add `<?php smart_archives(); ?>` anywhere in your theme, where you want the archives displayed.

See http://scribu.net/wordpress/smart-archives-reloaded for more usage examples.

== Frequently Asked Questions ==

= Error on activation: "Parse error: syntax error, unexpected..." =

Make sure your host is running PHP 5. The only foolproof way to do this is to add this line to wp-config.php (after the opening `<?php` tag):

`var_dump(PHP_VERSION);`
<br>

= Fancy archive not working =

In footer.php in your theme directory, make sure you have this code somewhere:

`<?php wp_footer(); ?>`.

= How can I change the CSS or HTML? =

Read this: [Advanced Tweaking](http://scribu.net/wordpress/smart-archives-reloaded/advanced-tweaking.html)

== Screenshots ==

1. A fancy archive
2. The Settings Page

== Changelog ==

= 2.0.5 =
* prevent useless SQL queries
* use latest scbFramework

= 2.0.4 =
* fixed menu when on date archives
* WP 3.1 compatibility

= 2.0.3 =
* fixed year order in menu & fancy modes
* improved memory usage

= 2.0.2 =
* fixed year order in block
* highlighted current month in block
* updated scbFramework

= 2.0.1 =
* fixed category exclusion

= 2.0 =
* added %excerpt% tag
* fewer queries
* advanced post selection
* removed caching engine
* [more info](http://scribu.net/wordpress/smart-archives-reloaded/sar-2-0.html)

= 1.9.2 =
* re-enabled cache

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

