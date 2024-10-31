=== Plugin Name ===
Contributors: frankyghost
Donate link: http://www.projekktor.com/license.php#donate
Tags: video, audio, html5, playlist, shortcode, webtv
Requires at least: 2.0.2
Tested up to: 3.1.3
Stable tag: 0.9.8

Adds shortcodes to embed HTML5 video (and audio) with flash-fallback based on the free (GPL) Projekktor player. Also generates a playlist.

== Description ==

This plugins adds easy to learn shortcodes allowing you to embed a HTML5 based audio and video player into your blog posts with ease.
The player features:

*   Six premium player themes
*   automatic Flash fallback
*   custom Logo Overlay
*   Youtube support
*   Social features  (embedding, post to Twitter and Facebook)


Additionally - if not most important - this plugin collects all videos from your published posts and generates
a JSON formatted - Projekktor ready - playlist out of them. This allows you to provide a stunning Hulu-resque
webTV video experience without changing the general way you manage your blog content.

When hacking new articles use at minimum [video src="URL to file"] in order to embed a video into your post.

Please visit http://www.projekktor.com/docs/wp for more detailed instructions.


== Installation ==

1. Upload the unzipped plugin folder to your plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Done.




== Frequently Asked Questions ==

= No questions asked so far =

No Answers required so far. Please feel free to ask at http://www.projekktor.com/support/


== Screenshots ==

1. Projekktor Maccaco Theme
2. Projekktor Minimum Theme
3. Projekktor HALL 2 Theme


== Changelog ==

= 0.9.8 =

additions:
* More themes
* added audio/m4a type
* added "x-webkit-airplay=allow" for native media
* added keyboard support

changes:
* Removed the "enable projekktor.js" option.

fixes:
* Opera "networkState" issues
* general jQuery 1.6 issues
* "resize" issues on pages with multiple player instances
* progress bar is now properly set on flash fallback with instant ready (cached) media
* added workaround for "click on transparent div" bug of IE6-9
* fixed scaling issues
* fixed minor issues with embed code 


= 0.9.7 =

In fact a complete rewrite of all Wordpress relevant PHP code plus some JS bonus:

changes:

* please do not force me to write them all down.

fixes:

* automatic type detection fixed in "embedded" mode
* "loop" config parameter now works properly in conjunction playlists
* minor visual issues fixed
* iPad start button now displays properly
* "playbutton" does not appear while switching between scheduled media items any longer
* "autoplay" config option now works properly
* controlbar is now disabled on "autoplay" eq. TRUE and "controls" eq. FALSE - as intended
* player now properly "stops" if set to "autoplay" and all scheduled videos played back once.
* fixed "play/pause" toggle issues while on jaris flash-fallback in Opera and IE
* fixed volume change issues while on jaris flash-fallback in Opera and IE
* youtube playback performance increased (fixed update timer issues)
* "cued" event on youtube playback is now properly handled
* fixed general IE9 issues - keep fingers crossed
* full-viewport-toggle on flashfallback fixed - listeners are now properly re-applied
* disabled fullscreen option on x-site iframe embeds in order to avoid JS security errors
* fixed "width" and "height" handling
* proper detection of "<source>" tags
* fixed youtube-model - time is now displayed properly even on two youtube videos in a row
* youtube videos now play properly on ipad, iphone and ipod devices
* ipad, iphone and ipod issues fixed
* buffering issues fixed
* dozens of cross browser issues fixed...

additions:

* poster and title are fetched from YT API in case none is set in config
* video and image rescaling on window.resize
* during load of xml or json playlists the player is now locked until loading procedure has finished
* basic i* playlist support
* Javascript API
* Share on Twitter, Facebook and as embeddable widget



= 0.9.6r1 =
* fixed empty flash & js dirs - thanx to Cindy

= 0.9.6 =
* added audio support
* fixed playlist issue for servers using PHP <5.2
* upgrade to Projekktor V0.7.14
  which fixes several IE issues and general glitches, adds iPad and iPhone support for single files and much more:
  Please refer http://www.projekktor.com/downloads.php#changelog to learn more.

= 0.9.5 =
* upgrade to Projekktor V0.7.12
  Please refer http://www.projekktor.com/downloads.php#changelog to learn more.

= 0.9.4 =
* fixed "no poster" issue
* plugin now automatically loads jQuery 1.4.2 if an older version is present
* disbaled loading of projekktor-JS files while in admin panel
* added default "width" and "height" config panel options

= 0.9.3 =
* updated to Projekktor V0.7.10, FIXES filetype detection issues. Please refer http://www.projekktor.com/downloads.php#changelog to learn more.

= 0.9.2 =
* updated to Projekktor V0.7.8, FIXES several bug and compatibility issues. Please refer http://www.projekktor.com/downloads.php#changelog to learn more.
* added webm video support
* added option for disabling flash fallback
* added default poster option
* added theme downloader
* removed remotely hosted script option

= 0.9.1 =
* moved projekktor.js from footer to header
* fixed internal path issues

= 0.9.0 =
* inital release


== Upgrade Notice ==

nothing to say so far