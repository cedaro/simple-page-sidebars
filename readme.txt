=== Simple Page Sidebars ===
Contributors: blazersix, bradyvercher
Donate link: http://bit.ly/s2zcgD
Tags: sidebars, custom sidebars, dynamic sidebar, simple, widget, widgets
Requires at least: 3.4.2
Tested up to: 3.5.1
Stable tag: trunk
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily assign custom, widget-enabled sidebars to any page.

== Description ==

Designed for simplicity and flexibility, Simple Page Sidebars gives WordPress users, designers, and developers the ability to assign custom sidebars to individual pages--without making any template changes. Existing sidebars can also be assigned in quick edit and bulk edit modes, helping save you time.

= Benefits =

* No more site-wide, generic sidebars. Each page (or section) can have its own widgets.
* Complete control over sidebar names.
* Assign the same sidebar to multiple pages.
* A page's sidebar can be modified without creating a pointless revision.

Simple Page Sidebars also comes with a "Widget Area" widget for including all the widgets from one sidebar into another.

== Installation ==

Installing Simple Page Sidebars is just like installing most other plugins. [Check out the codex](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) if you have any questions.

#### Setup
After installation, go to the Reading options panel (the Reading link under Settings) and choose which registered sidebar is the default sidebar.

== Frequently Asked Questions ==

= Why is the default sidebar still showing after I've created a custom sidebar for a page? =

If you haven't added any widgets to your new custom sidebar, the default sidebar will continue to display. If you really want a blank sidebar, just add an empty text widget.

= How do I give my blog a different sidebar? =

We recommend that you set your blog to use the default sidebar and create custom sidebars for pages (including the front/homepage). That way your blog page and posts all have the same sidebar.

However, if you defined a page for your posts in the Reading settings panel and assigned a custom sidebar to that page, that will work, too.

= Can I hide the "Sidebar" column on the Pages screen in the admin panel? =

Yes, just click the "Screen Options" tab in the upper right corner of your screen and uncheck the "Sidebar" option.

== Screenshots ==

1. Simply create a new sidebar when editing a page.
2. The new sidebar shows up on the widget panel. Notice the new "Widget Area" widget for including other widget areas.
3. Bulk edit in action. Easily assign a sidebar to multiple pages. (Quick edit works, too!)

== Notes ==

The philosphy behind creating this plugin was to make it easy to use and integrate it into the WordPress admin panel as seamlessly as possible. It's not the end-all, be-all solution for custom sidebars, but should handle the majority of use cases. We contemplated adding additional features and could have created an options page, but wanted to keep it simple and probably would have polluted it with credit meta boxes and whatnot.

The aim is basic, core-like functionality and integration.

= Custom Loops =

If your page has any custom loops or queries, they need to be followed by `wp_reset_query()`, otherwise the global `$post` variable will no longer reference the correct post and by the time the sidebar is displayed, Simple Page Sidebars won't know which page is being viewed, which can lead to an unexpected sidebar being displayed.

= Theme Sidebars =

Some themes create different sidebars for their various page templates, which means there isn't a default sidebar that can be replaced. The only workaround to continue using Simple Page Sidebars in this instance is to create a child theme to force page templates with custom sidebars to use the default sidebar.

== Changelog ==

= 1.1.2 =
* Changed the parent file of the "Edit Sidebar" screen to remove the small gap between submenu items.

= 1.1.1 =
* Worked around the slashing weirdness in WordPress API.
* Implemented a method to allow developers to easily add support for additional post types. No plans to build this out further, it's just here for additional flexibility if more complex solutions aren't wanted.
* Added a filter to disable the edit link in the custom Sidebar column (`simple_page_sidebars_show_edit_link_in_column`).

= 1.1 =
* Added an Edit Sidebar screen for updating a sidebar name and associated pages.
* Added an update message when a sidebar is saved on the Add/Edit Page screen.
* Made the sidebar column sortable on the All Pages screen.
* Refactored the codebase (formatting, improved comments, static classes, organization, etc).
* Added better feedback throughout the dashboard when something goes wrong.
* Saved spinner image to plugin folder due to updates coming in 3.5.
* Removed deprecated filters.

= 1.0.1 =
* Fixed bug causing issues with other plugins that don't submit the sidebar nonce on the All Pages screen.

= 1.0 =
* Modified check for blog page.

= 0.2.1 =
* Now works for the blog page when it's set in the Reading Settings.
* Bug fixes.

= 0.2 =
* Added an option to define the default sidebar on the Reading options panel.
* Removed the template change requirement. It's no longer recommended.
* Refactored code, including function/hook names.
* Deprecated `simple_sidebar` function. Replaced by `simple_page_sidebar`.
* Deprecated `simpsid_widget_areas` filter. Replaced by `simple_page_sidebars_widget_areas`.
* Deprecated `simpsid_widget_area_defaults` filter. Replaced by `simple_page_sidebars_widget_area_defaults`.
* Deprecated `simpsid_sidebar_name` filter. Replaced with `simple_page_sidebars_last_call`.

= 0.1 =
* Initial release.