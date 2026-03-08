=== Joker Meting Player ===
Contributors: JustJoker
Tags: music player, aplayer, meting
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.4.0-pro
License: MIT
License URI: https://opensource.org/licenses/MIT

Professional music player based on APlayer and Meting API, supporting custom API, automatic dark mode switching and deep UI customization.

== Description ==
Joker Meting Player is a high-performance music player plugin designed for WordPress, built on APlayer and Meting API. It supports multiple music platforms including NetEase Cloud Music, QQ Music, Kugou Music, Baidu Music and Xiami Music, with the following core features:

### Core Features
✅ Support for multiple music platforms (NetEase/QQ/Kugou/Baidu/Xiami)  
✅ Intelligent transient cache: API data is cached locally for 1 hour to improve loading speed and avoid IP bans  
✅ Dark mode: Support follow system/timed switch/always dark/always light  
✅ Deep UI customization: Full configuration of position/color/lyric size/play order  
✅ Mobile adaptation: Automatically adapt to mobile/tablet and other devices  
✅ Security reinforcement: Nonce verification/data sanitization/CSRF protection  

### Use Cases
- Add background music to personal blogs  
- Display playlists/singles on music-related websites  
- Embed brand theme songs on corporate official websites

== Installation ==
1. Download the plugin zip package, unzip it and upload to the `/wp-content/plugins/joker-meting-player/` directory;
2. Activate the "Joker Meting Player" plugin in the WordPress backend "Plugins" menu;
3. Go to the "Joker Music" settings page to configure playlist ID, music platform, API address and other parameters;
4. After saving the settings, the player will be automatically loaded to the front end of the website.

== Frequently Asked Questions ==
= Why can't the player load the playlist? =
1. Check if the playlist ID is correct (only numeric IDs are supported);
2. Confirm that the Meting API interface is accessible;
3. If VIP mode is enabled, check if the Cookie is valid;
4. Click "Reset to Default" to clear the cache and try again.

= Why is dark mode not working? =
- Timed mode requires a valid time format (e.g. 20:00);
- Follow system mode depends on the dark mode settings of the browser/operating system;
- Switch to "Always Dark" mode to test if it is a configuration issue.

= Abnormal display on mobile devices? =
The plugin has mobile adaptation built-in. If problems persist:
1. Check if the theme overrides the player style;
2. Adjust the lyric font size (minimum 12px);
3. Switch the player position (recommended bottom left/bottom right).

== Changelog ==
= 1.4.0-pro =
- Added API transient cache mechanism to improve loading speed and avoid IP bans
- Optimized mobile adaptive layout to fix list blank/occlusion issues
- Added Nonce verification to strengthen CSRF security protection
- Improved data sanitization logic to comply with WordPress specifications
- Fixed unescaped admin_url output issue
- Optimized dark mode timing logic

= 1.3.0 =
- Initial version release
- Basic player functionality
- Simple color customization

== Upgrade Notice ==
= 1.4.0-pro =
This update mainly fixes security and compatibility issues, and it is recommended for all users to upgrade. After upgrading, you need to save the settings again to trigger cache cleaning.
