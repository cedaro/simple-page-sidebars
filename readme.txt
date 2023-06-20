# Simple Page Sidebars

Contributors: cedaro, bradyvercher
Tags: sidebars, custom sidebars, dynamic sidebar, simple, widget, widgets
Requires at least: 4.9
Tested up to: 6.2
Stable tag: 1.2.1
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily assign custom, widget-enabled sidebars to any page.

## Description

Designed for simplicity and flexibility, Simple Page Sidebars gives WordPress users, designers, and developers the ability to assign custom sidebars to individual pages--without making any template changes. Existing sidebars can also be assigned in quick edit and bulk edit modes, helping save you time.

In contrast to some of the more complicated plugins available, Simple Page Sidebars aims for basic, core-like functionality and integration that's easy to use without polluting your admin panel. And due to the way sidebars are saved, it utilizes built-in WordPress caching, so your site won't be bogged down with additional queries.

Simple Page Sidebars also ships with a "Widget Area" widget for pulling all the widgets from one sidebar into another.

### Benefits

* No more site-wide, generic sidebars. Each page (or section) can have its own widgets.
* Complete control over the names of your custom sidebars.
* Assign the same sidebar to multiple pages.
* Modify a page's sidebar without creating an unnecessary revision.

### Advanced Usage

If you want to assign custom sidebars to archive pages or replace multiple sidebars per page, this plugin likely won't be the best solution. However it's flexible enough to handle a wide range of page-based use cases. It can even be configured to work with Custom Post Types by adding a couple lines of code:

`function myprefix_init() {
	add_post_type_support( '{{post_type}}', 'simple-page-sidebars' );
}
add_action( 'init', 'myprefix_init' );`

### Additional Resources

* [Write a review](https://wordpress.org/support/view/plugin-reviews/simple-page-sidebars#postform)
* [Contribute on GitHub](https://github.com/cedaro/simple-page-sidebars)
* [Follow @cedaroco](https://twitter.com/cedaroco)
* [Visit Cedaro](https://www.cedaro.com/?utm_source=wordpress.org&utm_medium=link&utm_content=simple-page-sidebars-readme&utm_campaign=plugins)

## Installation

Installing Simple Page Sidebars is just like installing most other plugins. [Check out the codex](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins) if you have any questions.

### Setup

After installation, go to the Reading options panel (the Reading link under Settings) and choose which of your registered sidebars is the default sidebar.

## Frequently Asked Questions

#### Why is the default sidebar still showing after I've created a custom sidebar for a page?

If you haven't added any widgets to your new custom sidebar, the default sidebar will continue to display. If you really want a blank sidebar, try adding an empty text widget.

#### How do I give my blog a different sidebar?

We recommend that you set your blog to use the default sidebar and create custom sidebars for pages (including the front/homepage). That way your blog page and posts all have the same sidebar.

However, if you defined a page for your posts in the Reading settings panel and assigned a custom sidebar to that page, that will work, too.

#### Can I hide the "Sidebar" column on the Pages screen in the admin panel?

Yes, just click the "Screen Options" tab in the upper right corner of your screen and uncheck the "Sidebar" option.

## Screenshots

1. Simply create a new sidebar when editing a page.
2. The new sidebar shows up on the widget panel. Notice the new "Widget Area" widget for including other widget areas.
3. Bulk edit in action. Easily assign a sidebar to multiple pages. (Quick edit works, too!)

## Notes

### Custom Loops

If your page has any custom loops or queries, they need to be followed by `wp_reset_query()`, otherwise the global `$post` variable will no longer reference the correct post and by the time the sidebar is displayed, Simple Page Sidebars won't know which page is being viewed, possibly leading to an unexpected sidebar being displayed.

### Theme Sidebars

Some themes register different sidebars for their page templates, which means there isn't a default sidebar that can be replaced. To use Simple Page Sidebars in this instance, you can create a child theme and force page templates with custom sidebars to use the default sidebar.

## Changelog

### 1.2.1 - July 27, 2018
* Removed bundled language files in favor of WordPress.org language packs.

### 1.2.0
* Transferred to Cedaro.
* Updated the Widget Area class constructor to prevent deprecation notices in WP 4.3+.

### 1.1.8
* Added Spanish translation.

### 1.1.7
* Added Indonesian translation.

### 1.1.6
*  Prevent quick edit nonces from being submitted when searching or filtering a post list table.

### 1.1.5
* Added Serbo-Croatian translation.

### 1.1.4
* Really fix the Quick Edit functionality.
* Update text domain loading order to get ready for language packs.
* Fix a strict PHP notice.

### 1.1.3
* Fixed Quick Edit functionality in WordPress 3.6.

### 1.1.2
* Changed the parent file of the "Edit Sidebar" screen to remove the small gap between submenu items.

### 1.1.1
* Worked around the slashing weirdness in WordPress API.
* Implemented a method to allow developers to easily add support for additional post types. No plans to build this out further, it's just here for additional flexibility if more complex solutions aren't wanted.
* Added a filter to disable the edit link in the custom Sidebar column (`simple_page_sidebars_show_edit_link_in_column`).

### 1.1
* Added an Edit Sidebar screen for updating a sidebar name and associated pages.
* Added an update message when a sidebar is saved on the Add/Edit Page screen.
* Made the sidebar column sortable on the All Pages screen.
* Refactored the codebase (formatting, improved comments, static classes, organization, etc).
* Added better feedback throughout the dashboard when something goes wrong.
* Saved spinner image to plugin folder due to updates coming in 3.5.
* Removed deprecated filters.

### 1.0.1
* Fixed bug causing issues with other plugins that don't submit the sidebar nonce on the All Pages screen.

### 1.0
* Modified check for blog page.

### 0.2.1
* Now works for the blog page when it's set in the Reading Settings.
* Bug fixes.

### 0.2
* Added an option to define the default sidebar on the Reading options panel.
* Removed the template change requirement. It's no longer recommended.
* Refactored code, including function/hook names.
* Deprecated `simple_sidebar` function. Replaced by `simple_page_sidebar`.
* Deprecated `simpsid_widget_areas` filter. Replaced by `simple_page_sidebars_widget_areas`.
* Deprecated `simpsid_widget_area_defaults` filter. Replaced by `simple_page_sidebars_widget_area_defaults`.
* Deprecated `simpsid_sidebar_name` filter. Replaced with `simple_page_sidebars_last_call`.

### 0.1
* Initial release.
