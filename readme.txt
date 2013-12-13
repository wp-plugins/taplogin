=== TapLogin ===
Contributors: matrixplatform
Donate link: N/A
Tags: login, authentication
Requires at least: 3.0
Tested up to: 3.7.1
Stable tag: trunk
License: Public domain

Single Sign-On service for secure login to web sites by tapping just one button on your phone, no password required.

== Description ==

TapLogin is a Single Sign-On service for logging in to web sites by tapping just one button in your phone, without entering a password, yet as secure as modern internet banking sites. Every time a user logs in to a site, TapLogin recognizes the user automatically and sends confirmation request to his phone. User approves login by tapping just one button in TapLogin app on the phone. Passwords are no longer needed, and security is ensured by use of two devices associated with the user: a computer and a phone.

Sites where TapLogin already works:
https://www.azid.ru
https://www.billinger.ru/control/

TapLogin will be displayed as a widget above your page content. In most cases the login dialog takes place within this widget and user never has to leave your site.

New users will be automatically added to your wordpress database. There is an option to auto-generate email addresses to further ease the registration process in case your site doesn't require emails.

Existing users can link their accounts at your site to their TapLogin accounts in their /wp-admin/, if they want to move from password based authentication to phone based authentication.

== Installation ==

1. Install the plugin from the Plugins page in your /wp-admin/
1. Activate the plugin
1. Go to Settings / TapLogin Settings. The secret key is already generated for you, copy it to clipboard
1. Sign up at https://www.azid.ru, go to nodes page https://www.azid.ru/control/nodes.php, add a "desktop node", paste the secret key from your WP admin. Take note of the Node ID of the newly generated node.
1. Return to Settings / TapLogin Settings, enter the generated Node ID and save (check or uncheck the email auto-generation option as you feel necessary).
1. That's it, test it at your login page, TapLogin widget should display above your page. Close the widget if you want to login with a password.

== Frequently Asked Questions ==

= Do I have to pay? =

Usually, no. Refer to our [site](https://www.azid.ru/sites.php) for details.

= Do my users pay anything? =

No, nothing, apart from tiny network costs. Also see our [users' FAQ](https://www.azid.ru/faq.php).

== Screenshots ==

1. An example WP login page with TapLogin widget above it.

== Changelog ==

= 1.0 =
Initial release.

== Upgrade Notice ==

= 1.0 =
N/A
