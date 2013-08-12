=== BotSmasher ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate.php
Tags: botsmasher, spam, anti-spam, captcha, comments, registration, contact form
Requires at least: 3.4.2
Tested up to: 3.6.0
Stable tag: 1.0.0
License: GPLv2 or later

BotSmasher stops spam by checking comments and registrations against the BotSmasher API. 

== Description ==

<strong>Got spam?</strong> BotSmasher wants to fix that problem. 

BotSmasher is a CAPTCHA-less tool to check user submissions for spam. Why CAPTCHA-less? Because your spam isn't your visitors' problem.

<strong>Comments</strong>

All comments supplied by users who aren't administrators are checked by BotSmasher. If they are identified as spam, they'll be flagged as spam and not shown on your site. You can report false positives back to BotSmasher if a real comment gets flagged.

<strong>Registrations</strong>

New registrations are run through BotSmasher. Anybody flagged by BotSmasher will get a notice that they've been flagged as spam, and directed to contact the site separately. When you get the new user email from WordPress, it'll include a link to the user's profile that enables you to flag that user in BotSmasher.

<strong>Contact Form</strong>

BotSmasher includes an integrated and accessible contact form with basic customization options. The contact form shortcode is documented on the settings page, but can also be configured via the widget tool. 

== Installation ==

1. Download the plugin's zip file, extract the contents, and upload them to your wp-content/plugins folder.
2. Login to your WordPress dashboard, click "Plugins", and activate BotSmasher.
3. At http://www.botsmasher.com, register to get an API key.
2. Add your API key on the Settings > BotSmasher screen.

== Changelog ==

= 1.0.0 =

* Initial release!

== Frequently Asked Questions ==

= BotSmasher is an awesome service! But I need to use some other contact form. What can I do? =

We're working on coming up with tools that can be installed to provide filtering for other contact forms. But you can also contact your favorite contact form creator and invite them to integrate BotSmasher into their form as a filtering option!

= Is there a limit on queries against the BotSmasher API? =

Right now, yes. The standard API key grants you 100 spam checks a day. This may change, but if you need more queries, [contact BotSmasher](http://www.botsmasher.com/contact.php).

= BotSmasher didn't catch all my spam! =

Catching every piece of spam is, honestly, pretty darned hard. I can't really guarantee that BotSmasher will catch every piece of spam you get -- but if you keep submitting missed spam to the database, it'll just keep getting better!

== Screenshots ==

1. Settings Page
2. Contact form widget.

== Upgrade Notice ==

Nothing to upgrade yet.