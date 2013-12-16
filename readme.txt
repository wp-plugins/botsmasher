=== BotSmasher ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate.php
Tags: botsmasher, spam, anti-spam, captcha, comments, registration, contact form
Requires at least: 3.4.2
Tested up to: 3.8.0
Stable tag: 1.0.5
License: GPLv2 or later

BotSmasher stops spam by checking comments and registrations against the BotSmasher API. 

== Description ==

<strong>Got spam?</strong> BotSmasher wants to fix that problem. 

BotSmasher is a CAPTCHA-less tool to check user submissions for spam. Why CAPTCHA-less? Because your spam isn't your visitors' problem.

[Register with BotSmasher](http://www.botsmasher.com/register.php) to get an API key and start smashing bots!

= Comments =

All comments supplied by users who aren't administrators are checked by BotSmasher. If they are identified as spam, they'll be flagged as spam and not shown on your site. You can report false positives back to BotSmasher if a real comment gets flagged.

= Registrations =

New registrations are run through BotSmasher. Anybody flagged by BotSmasher will get a notice that they've been flagged as spam, and directed to contact the site separately. When you get the new user email from WordPress, it'll include a link to the user's profile that enables you to flag that user in BotSmasher.

= Contact Form =

BotSmasher includes an integrated and accessible contact form with basic customization options. The contact form shortcode is documented on the settings page, but can also be configured via the widget tool. Customize styles by placing a stylesheet called 'bs-form.css' in your theme or child theme directory.

= Translations =

Available languages (in order of completeness):
There aren't any yet!

Visit the [BotSmasher translations site](http://translate.joedolson.com/projects/botsmasher) to help out!

Translating my plug-ins is always appreciated. Visit <a href="http://translate.joedolson.com">my translations site</a> to start getting your language into shape!

<a href="http://www.joedolson.com/articles/translator-credits/">Translator Credits</a>

== Installation ==

1. Download the plugin's zip file, extract the contents, and upload them to your wp-content/plugins folder.
2. Login to your WordPress dashboard, click "Plugins", and activate BotSmasher.
3. At http://www.botsmasher.com, register to get an API key.
4. Add your API key on the Settings > BotSmasher screen.

== Changelog ==

= 1.0.6 =

* New filter: bs_custom_field - generate a custom input field.
* New filter: bs_draw_message - generate a custom response message.
* Miscellaneous bug fixes.
* 

= 1.0.5 =

* Bug fix: Missing argument in bs_submit_form();
* Bug fix: Form could be submitted with blank name/email fields.
* Bug fix: Return POST data on spam and blank name/email errors.
* Bug fix: Form was still sent if required fields blank.
* Bug fix: Form errors for 'name' and 'email' fields was not displayed.
* Bug fix: Enabling HTML email did not work.
* Minor style changes on front end forms.
* Added documentation for contact form shortcode.

= 1.0.4 =

* Miscellaneous warnings and notices in contact form submission.
* Bug fixes in local spam registry pre-check.
* Bug fix in saving option to send HTML email.

= 1.0.3 =

* Added link to BotSmasher registration page by API key field.
* Added text to readme about registering with BotSmasher.
* Improved debugging log, modified settings page layout.
* Bug fix: check if registration message function already declared.
* Bug fix: If API JSON data has error message appended, strip it so data is parseable.

= 1.0.2 =

* Fixed FAQ link in support request form.
* Fixed support form nonce
* Lengthened timeout period on HTTP requests
* Added WP errors to error log.
* Ability to enable debugging.
* Added botsmasher.pot to download. 

= 1.0.1 =

* First bug fixed: debugging message left in client class.

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