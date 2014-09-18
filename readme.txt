=== Teddy ID ===
Contributors: matrixplatform
Donate link: N/A
Tags: login, authentication, password, passwordless, sign in, 2FA, TFA, mfa, log in, multi-factor, two factor, sso, single sign-on, 2-factor, two-factor authentication
Requires at least: 3.0
Tested up to: 4.0
Stable tag: trunk
License: Public domain

Single Sign-On service for secure login to web sites by tapping just one button on your phone, no password required.

== Description ==

Teddy ID is a Single Sign-On service for logging in users to your site by tapping just one button on user's phone, without entering a password, yet as secure as modern internet banking sites. Every time a user logs in to your site, TeddyID recognizes the user automatically and sends a picture to his phone and to his computer. User compares the pictures and approves login by tapping just one button in TeddyID app on his phone. Passwords are no longer needed, and security is ensured by use of two devices associated with the user: a computer and a phone. This is two factor authentication made simple.

Sample sites where TeddyID already works:
https://www.TeddyID.com
https://www.billinger.ru/control/

TeddyID will be displayed as a widget above your page content. In most cases the login dialog takes place within this widget and user never has to leave your site.

New users will be automatically added to your wordpress database. There is an option to auto-generate email addresses to further ease the registration process in case your site doesn't require emails.

Existing users can link their accounts at your site to their TeddyID accounts in their /wp-admin/ if they want to move from password based authentication to phone based authentication.

== Installation ==

1. Install the plugin from the Plugins page in your /wp-admin/
1. Activate the plugin
1. Go to Settings / TeddyID Settings. The secret key is already generated for you, copy it to clipboard
1. Sign up at https://www.TeddyID.com, go to nodes page https://www.TeddyID.com/control/nodes.php, add a "desktop node", paste the secret key from your WP admin. Take note of the Node ID of the newly generated node.
1. Return to Settings / TeddyID Settings, enter the generated Node ID and save (check or uncheck the email auto-generation option as you feel necessary).
1. That's it, test it at your login page, TeddyID widget should display above your page. Close the widget if you want to login with a password.

== Frequently Asked Questions ==

= Do I have to pay? =

Usually, no. Refer to our [site](https://www.TeddyID.com/sites.php) for details.

= Do my users pay anything? =

No, nothing, apart from tiny network costs. Also see our [users' FAQ](https://www.TeddyID.com/faq.php).

== Screenshots ==

1. An example WP login page with TeddyID widget above it.
1. An example WP login page with confirmation picture. User needs to compare this picture with the picture sent to his phone and tap Yes on the phone. He'll be logged in in a few seconds.

== Changelog ==

= 1.0 =
Initial release.

= 1.2 =
Now user compares two pictures, on your site and on his phone, and if they match, user approves the login.

= 1.2.1 =
Minor bugfixes.

== Upgrade Notice ==

= 1.0 =
N/A

= 1.2 =
N/A
