<?php
/**
 * Banner settings. Copy this file to config.php and edit it there.
 * Your ServerQuery login does NOT go here: it goes in secret.php.
 */
return [
    'title'      => 'MY TEAMSPEAK',        // big header text
    'address'    => 'ts.example.com',      // shown under the title and in the footer
    'website'    => 'example.com',         // footer, bottom left

    'timezone'   => 'Europe/Brussels',     // any PHP time zone name
    'zone_label' => 'Brussels',            // shown next to the date, followed by CET/CEST etc.

    'logo'       => __DIR__ . '/assets/logo.png',        // PNG with transparency works best
    'background' => __DIR__ . '/assets/background.jpg',  // any JPG/PNG, scaled to fill
    'accent'     => '42A5F5',              // main colour (hex) outside the seasonal themes

    'seasonal_themes' => true,             // false = always use the default look

    // Small facts under the clock.
    'show' => ['ping' => true, 'loss' => true, 'peak' => true, 'visits' => true],

    // Where stats and finished images are cached ('' = the system temp folder).
    'cache_dir'  => '',
];
