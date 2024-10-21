=== bbPress Restrict Membership Forum & Private Replies for Members Only with Paid Memberships Pro ===
Contributors: strangerstudios, paidmembershipspro
Tags: discussion, forum, bbpress, paid memberships pro, pmpro
Requires at least: 5.2
Tested up to: 6.6
Requires PHP: 5.6
Stable tag: 1.8

Restrict access to bbPress for free or premium members by integrating bbPress with the top WordPress membership plugin Paid Memberships Pro.

== Description ==

### The most popular WordPress plugin for private forums.

Use bbPress and Paid Memberships Pro to create a private forum in your WordPress site. This plugin enables secure, private discussion boards for members of your free or paid community.

= Private Forums: Restrict Forum Access by Level =
With private forums, all content in the forum is secured from public view. Select the membership levels that are allowed to access the restricted forum. Any member, user, or visitor without access is blocked from viewing forums, topics, and replies.

* Members get quick access to the private forum on the "Member Links" section of the "Membership Account" page.
* Site admins can optionally choose to hide the forum name from the forums list and search results.
* To create a better forum experience for your members, choose from the settings to "Hide Forum Roles" and "Show Membership Levels" in replies and on the bbPress profile page. This helps participants see what level of membership access another member has.

= Forum Roles: Set Forum Role by Membership Level =
Give certain membership levels a higher tier of access within your forum. Assign the forum role by membership level in your Paid Memberships Pro settings. This forum role is changed or removed from the member when their membership level changes.

For example, allow certain membership levels to be spectators in the forum, while you allow other higher tier members access to moderate forum replies.

[Learn more about bbPress private forums in our documentation site](https://www.paidmembershipspro.com/add-ons/pmpro-bbpress/?utm_source=wordpress-org&utm_medium=readme&utm_campaign=pmpro-bbpress).

### About Paid Memberships Pro

[Paid Memberships Pro is a WordPress membership plugin](https://www.paidmembershipspro.com/?utm_source=wordpress-org&utm_medium=readme&utm_campaign=pmpro-bbpress) that puts you in control. Create what you want and release in whatever format works best for your business.

* Courses & E-Learning
* Private podcasts
* Premium Newsletters
* Private Communities
* Sell physical & digital goods

Paid Memberships Pro allows anyone to build a membership site—for free. Restrict content, accept payment, and manage subscriptions right from your WordPress admin.

Paid Memberships Pro is built "the WordPress way" with a lean core plugin and over 75 Add Ons to enhance every aspect of your membership site. Each business is different and we encourage customization. For our members we have a library of 300+ recipes to personalize your membership site.

Paid Memberships Pro is the flagship product of Stranger Studios. We are a bootstrapped company which grows when membership sites like yours grow. That means we focus our entire company towards helping you succeed.

[Try Paid Memberships Pro entirely for free on WordPress.org](https://wordpress.org/plugins/paid-memberships-pro/) and see why 100,000+ sites trust us to help them #GetPaid.

### Read More

Want more information on private forums, premium discussion boards, and WordPress membership sites? Have a look at:

* The [Paid Memberships Pro](https://www.paidmembershipspro.com/?utm_source=wordpress-org&utm_medium=readme&utm_campaign=pmpro-bbpress) official homepage.
* The [bbPress Integration for PMPro documentation page](https://www.paidmembershipspro.com/add-ons/pmpro-bbpress/?utm_source=wordpress-org&utm_medium=readme&utm_campaign=pmpro-bbpress).
* Also follow PMPro on [Twitter](https://twitter.com/pmproplugin), [YouTube](https://www.youtube.com/channel/UCFtMIeYJ4_YVidi1aq9kl5g) & [Facebook](https://www.facebook.com/PaidMembershipsPro/).

== Installation ==

Note: You must have [Paid Memberships Pro](https://wordpress.org/plugins/paid-memberships-pro/) and [bbPress](https://wordpress.org/plugins/bbpress/) installed and activated on your site.

### Install PMPro bbPress from within WordPress

1. Visit the plugins page within your dashboard and select "Add New"
1. Search for "PMPro bbPress"
1. Locate this plugin and click "Install"
1. Activate "Paid Memberships Pro - bbPress Integration" through the "Plugins" menu in WordPress
1. Go to "after activation" below.

### Install PMPro bbPress Manually

1. Upload the `pmpro-bbpress` folder to the `/wp-content/plugins/` directory
1. Activate "Paid Memberships Pro - bbPress Integration" through the "Plugins" menu in WordPress
1. Go to "after activation" below.

### After Activation: Create a Private Forum

1. Navigate to Forums > Edit Forum to restrict access to the forum and make it private.
1. Or, create a new Forum under Forums > Add New.
1. In the right column "Settings" panel, locate the "Require Membership" section.
1. Check the box for one or more membership level that can access this forum.
1. Save your changes by clicking the "Update" button (or "Publish" if you are creating a new forum).
1. Further settings can be found on the Settings > Forums page of your admin dashboard.

### Shortcode for Member Activity

The bbPress Integration for Paid Memberships Pro includes one shortcode to display a member's activity (topics or replies created).

= Sample shortcode usage: =
`[bbp-user-activity activity_type="topic" show_date="true" title="My Recent Topics"]`

= Shortcode attributes include: =

* activity_type: Accepts 'topic' or 'reply'. Default is 'topic'
* bbp_user_id: Accepts any user ID. Omit this attribute to load the current user's entries. Default is the `current_user->ID`.
* count: The number of entries to show. Default is '5'.
* show_date: Optionally show the entry date. Default is 'false'.
* show_excerpt: Optionally show a 50-character excerpt of the entry. Default is 'false'.
* title: An optional title for the shortcode output, wrapped in the h2 class="widgettitle" format.

== Screenshots ==

1. The "Require Membership" meta box for controlling forum access by membership level.
2. Additional Private Forum Settings on the Settings > Forums screen in the WordPress admin.
3. Specifty additional bbPress settings specific to a membership level on the Memberships > Settings > Membership Levels screen in the WordPress admin.

== Changelog ==
= 1.7.4 - 2023-10-13 =
* ENHANCEMENT: Updating `<h3>` tags to `<h2>` tags for better accessibility. #47 (@michaelbeil)
* BUG FIX/ENHANCEMENT: Added compatibility for Multiple Memberships Per User. #44 (@dparker1005)
* BUG FIX/ENHANCEMENT: Improved escaping of strings. #40 (@rafiahmedd)
* REFACTOR: Now using `get_option()` instead of `pmpro_getOption()`. #46 (@JarrydLong)

= 1.7.3 - 2021-08-13 =
* BUG FIX: Fixed issue that was causing fatal errors when WP_Query didn't contain posts when other plugins were setting the query. Fixes an issue for Formidable Pro/Registrations and improves compatibility with other plugins.

= 1.7.2 - 2021-08-09 =
* BUG FIX: Fixed issue where fatal errors were thrown in the admin when using some other plugins, e.g. WP Form or Formidable Pro. (Thanks, steve-page on GitHub)

= 1.7.1 - 2020-10-03 =
* BUG FIX: Fixed fatal error that occurred if bbPress was not active.

= 1.7 - 2020-09-25 =
* BUG FIX: Fixed a warning that post_type wasn't set in some cases for the pre_get_posts.
* BUG FIX/ENHANCEMENT: Merged two functions that hooked in on 'init' to try and stabilize functionality.
* ENHANCEMENT: Added in a filter to bypass the "Filter searches and archives" settings for forums and topics from search and archive pages. Namely 'pmprobb_filter_forum_queries' and 'pmprobb_filter_topic_queries' (boolean values).
* ENHANCEMENT: Escape and localized strings to allow for translations and additional locales.

= 1.6 - 2020-01-01 =
* FEATURE: Actually changing user's bbPress Roles when changing levels if you've set a specific role for their level.

= 1.5.5 =
* BUG FIX: Fixed issue where the pmprobb_auth_reply_view filter was nuking content filters applied to bbpress replies before it.
* BUG FIX/ENHANCEMENT: Now only calling the pmpro_bbp_error_msg filter if we're going to show the error.
* ENHANCEMENT: Added pmprobbp_check_forum_redirect_url filter to allow filtering of URL to which users without access are redirected.

= 1.5.4 =
* BUG FIX: Fixed issues on edit membership level page when bbPress is not activated.
* BUG FIX: Fixed issue where non-members could access replies in member forums if linked to directly.

= 1.5.3 =
* BUG: Fixed fatal error when bbPress was not activated.

= 1.5.2 =
* ENHANCEMENT: When redirecting members away from protected forums, the referring page is now saved in $_SESSION['pmpro_bbp_redirected_from'].

= 1.5.1 =
* FEATURE: Added new shortcode for member activity (topics or replies).

= 1.5 =
* FEATURE: Added a membership level setting to set the background color of member topics and replies.
* FEATURE: Added an option to the bbPress settings page to change the error message shown when non-members try to access a member forum.
* FEATURE: Added an option to the bbPress settings page to add "member links" linking to forums a user has access to.
* FEATURE: Added an option to the bbPress settings page to hide member forums from the forums list and search results.
* FEATURE: Added an option to the bbPress settings page to hide forum roles in replies.
* FEATURE: Added an option to the bbPress settings page to show membership level in replies.

= 1.4 =
* Removed TGM and using different methods to make sure PMPro and bbPress are activated.
* Changed forum check to use template_redirect instead of wp hook.

= 1.3 =
* Updated TGM Plugin Activation class

= 1.2 =
* Added the "pmpro_bbp_error_msg" filter so you can change the message shown when users try to access forums they don't have access to.

= 1.1.5 =
* Added bbp_is_single_topic() check to search filter to fix issues where main topic is hidden on single topic pages. (Thans, Spence)

= 1.1.4 =
* Updates to name, description, tags. Added link to support and settings on plugins page.

= 1.1.3 =
* Fixed fatal error that would come up if Paid Memberships Pro was not active. (Thanks, Karmyn Tyler Cobb)

= 1.1.2 =
* BUG: Fixed bug in search filter that would hide member forums/topics from members when more than one level had access to a forum.

= 1.1.1 =
* Moved filterqueries code into init function to avoid function not found error.

= 1.1 =
* pmpro_search_filter now hides restricted forums and topics as well.

= 1.0.1 =
* Fixed generation of URLs &noaccess=1. The old URLs could sometimes lead to 404 pages. (Thanks, bfintal on the WordPress.org forums.)

= 1.0 =
* Initial WP.org release.
