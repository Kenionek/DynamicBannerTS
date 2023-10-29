<?php
error_reporting(E_ERROR | E_PARSE);

// Path for Teamspeak 3 PHP FRamework
require_once('libraries/TeamSpeak3/TeamSpeak3.php');

// TeamSpeak server query configuration
$ts3Username = 'serveradmin';
$ts3Password = 'YOURPASSWORD';
$ts3Server = 'YOUR_IP:QUERYPORT';

try {
    $ts3 = TeamSpeak3::factory("serverquery://$ts3Username:$ts3Password@$ts3Server/?server_port=9987");

    $onlineUsers = $ts3->clientCount();
} catch (Exception $e) {
    echo '<p>Error: ' . $e->getMessage() . '</p>';
}

// Create a new image with a width and height (You can slightly adjust)
$width = 900;
$height = 450;
$image = imagecreatetruecolor($width, $height);

// Define some colors
$backgroundColor = imagecolorallocatealpha($image, 0, 0, 0, 0); // Transparent background
$textColor = imagecolorallocate($image, 255, 255, 255); // White text
$transparentBackgroundColor = imagecolorallocatealpha($image, 0, 0, 0, 115); // Semi-transparent background color (45% transparency)
$font = 'RopaSans-Regular.ttf'; // Path to your font file

// Load the background image
$background = imagecreatefromjpeg('default.jpg'); // Change the image format if needed
imagecopy($image, $background, 0, 0, 0, 0, $width, $height);

// Fill the background with a transparent color
imagefill($image, 0, 0, $backgroundColor);

// Define styles (For text you want on banner (example what I have)
$bannerText = "DSnakes Official";
$bannerFontSize = 48; 
$bannerX = 30;    //these are the location of the text
$bannerY = 70;

$time = date("H:i", strtotime('-7 hours')); // Adjust time here
$timeFontSize = 112; // adjust size of time output
$timeX = 20;
$timeY = 280; // Adjusted location here

// Correct date to the desired timezone
$timezone = new DateTimeZone('Europe/Berlin'); // Replace 'your_timezone_here' with your desired timezone
$dateObject = new DateTime('now', $timezone);
$date = $dateObject->format('d/m/Y');
$dateFontSize = 48; // Adjust font size
$dateX = 20;
$dateY = 400;

// Load and paste the logo you want on banner
$logo = imagecreatefrompng('logo.png'); // Change the image format/name if needed
if ($logo) {
    $logoWidth = imagesx($logo);
    $logoHeight = imagesy($logo);
    $logoX = 10; // Adjust the position as needed
    $logoY = 20; // Adjust the position as needed
    imagecopy($image, $logo, $logoX, $logoY, 0, 0, $logoWidth, $logoHeight);

    //Output of online users online on TS server + adjustments
    $onlineUsersText = "$onlineUsers";
    $onlineUsersFontSize = 200; //  font size
    $onlineUsersX = 700; // Adjust the X coordinate as needed
    $onlineUsersY = 320; // Adjust Y coordinate as needed

    // Add text to the image with a semi-transparent background
    imagettftext($image, $bannerFontSize, 0, $bannerX, $bannerY, $textColor, $font, $bannerText);
    addSemiTransparentTextBackground($image, $bannerFontSize, $bannerX, $bannerY, $font, $bannerText, $transparentBackgroundColor);
    
    imagettftext($image, $timeFontSize, 0, $timeX, $timeY, $textColor, $font, $time);
    addSemiTransparentTextBackground($image, $timeFontSize, $timeX, $timeY, $font, $time, $transparentBackgroundColor);
    
    imagettftext($image, $dateFontSize, 0, $dateX, $dateY, $textColor, $font, $date);
    addSemiTransparentTextBackground($image, $dateFontSize, $dateX, $dateY, $font, $date, $transparentBackgroundColor);

    // Add the online users count text with a semi-transparent background
    imagettftext($image, $onlineUsersFontSize, 0, $onlineUsersX, $onlineUsersY, $textColor, $font, $onlineUsersText);
    addSemiTransparentTextBackground($image, $onlineUsersFontSize, $onlineUsersX, $onlineUsersY, $font, $onlineUsersText, $transparentBackgroundColor);

    // "Online Users" text (you can change the text, size and location here)
    $onlineUsersTitle = "Online Users";
    $onlineUsersTitleFontSize = 48; // Adjust the font size as needed
    $onlineUsersTitleX = $width - 20 - imagettfbbox($onlineUsersTitleFontSize, 0, $font, $onlineUsersTitle)[2];
    $onlineUsersTitleY = 20 + $onlineUsersTitleFontSize; // Adjusted Y coordinate
    imagettftext($image, $onlineUsersTitleFontSize, 0, $onlineUsersTitleX, $onlineUsersTitleY, $textColor, $font, $onlineUsersTitle);
    addSemiTransparentTextBackground($image, $onlineUsersTitleFontSize, $onlineUsersTitleX, $onlineUsersTitleY, $font, $onlineUsersTitle, $transparentBackgroundColor);
   
    // Create the grey bar at the bottom of the banner
    imagefilledrectangle($image, 0, $height - $barHeight, $width, $height, $greyColor);
    
    // Define the height of the grey bar at the botton of banner
    $barHeight = 30; // Adjust the height as needed

    // Define the grey color
    $greyColor = imagecolorallocate($image, 192, 192, 192); // You can adjust the RGB values as needed

    // Define font size for website and IP
    $smallFontSize = 24; // Slightly bigger font size

    // Define background color with 45% transparency (adjust as needed)
   
    // Define background color with 45% transparency (adjust as needed)
    $backgroundColorWithTransparency = imagecolorallocatealpha($image, 0, 0, 0, 115); // 45% transparency

    // Define website and IP
    $website = "Example.com";
    $ip = "ts.Example.IP";

    // Calculate text width for positioning
    $websiteWidth = imagettfbbox($smallFontSize, 0, $font, $website);
    $ipWidth = imagettfbbox($smallFontSize, 0, $font, $ip);

    // Calculate X positions for left-aligned website and right-aligned IP
    $websiteX = 10; // Adjust the position as needed for left alignment
    $ipX = $width - $ipWidth[2] - 10; // Adjust the position as needed for right alignment

    // Y position for both text elements (inside the grey bar)
    $textY = $height - $barHeight + (($barHeight - $smallFontSize) / 2); // Centered vertically within the grey bar

    // Add background for website text
    imagefilledrectangle($image, $websiteX, $textY, $websiteX + $websiteWidth[2], $textY + $smallFontSize, $backgroundColorWithTransparency);

    // Add website text
    imagettftext($image, $smallFontSize, 0, $websiteX, $textY + $smallFontSize, $textColor, $font, $website);

    // Add background for IP text
    imagefilledrectangle($image, $ipX, $textY, $ipX + $ipWidth[2], $textY + $smallFontSize, $backgroundColorWithTransparency);

    // Add IP text
    imagettftext($image, $smallFontSize, 0, $ipX, $textY + $smallFontSize, $textColor, $font, $ip);

    // Output the image as PNG
    header('Content-Type: image/png');
    @imagepng($image);
} else {
    echo 'Error loading the logo image.';
}

// Free up memory for the background
@imagedestroy($background);
@imagedestroy($image);

// Function to add semi-transparent background to text
function addSemiTransparentTextBackground($image, $fontSize, $x, $y, $font, $text, $backgroundColor) {
    $textColor = imagecolorallocate($image, 255, 255, 255);
    imagettftext($image, $fontSize, 0, $x, $y, $textColor, $font, $text);
    
    $bbox = imagettfbbox($fontSize, 0, $font, $text);
    $textWidth = abs($bbox[2] - $bbox[0]);
    $textHeight = abs($bbox[7] - $bbox[1]);
    
    imagefilledrectangle($image, $x - 10, $y - $textHeight - 10, $x + $textWidth + 10, $y + 10, $backgroundColor);
}
?>
