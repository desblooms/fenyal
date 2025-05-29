<?php
/**
 * Icon Generator Script for Fenyal Food Ordering PWA
 * 
 * This script generates all required icon sizes and splash screens from a single source image.
 * It requires the GD library to be installed in PHP.
 * 
 * Usage: php generate_icons.php [source_image.png]
 */

// Check if GD is installed
if (!extension_loaded('gd')) {
    die("Error: GD library is required but not installed on this server.\n");
}

// Set appropriate error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths
$basePath = __DIR__ . '/';
$sourcePath = isset($argv[1]) ? $argv[1] : $basePath . 'fenyal-logo.png';
$iconsDir = $basePath . 'assets/icons/';
$screenshotsDir = $basePath . 'assets/screenshots/';

// Check if source image exists
if (!file_exists($sourcePath)) {
    die("Error: Source image not found at: $sourcePath\n");
}

// Create icons directory if it doesn't exist
if (!is_dir($iconsDir)) {
    if (!mkdir($iconsDir, 0755, true)) {
        die("Error: Failed to create icons directory. Please check permissions.\n");
    }
    echo "Created icons directory: $iconsDir\n";
}

// Create screenshots directory if it doesn't exist
if (!is_dir($screenshotsDir)) {
    if (!mkdir($screenshotsDir, 0755, true)) {
        die("Error: Failed to create screenshots directory. Please check permissions.\n");
    }
    echo "Created screenshots directory: $screenshotsDir\n";
}

// Icon sizes for PWA
$sizes = [
    72, 96, 128, 144, 152, 192, 384, 512
];

// Additional special icons
$specialIcons = [
    'menu-icon' => 96,
   
];

// Splash screen sizes for iOS
$splashSizes = [
    '640x1136', '750x1334', '1242x2208', '1125x2436', 
    '1536x2048', '1668x2224', '2048x2732'
];

// Load the source image
$sourceImage = imagecreatefrompng($sourcePath);
if (!$sourceImage) {
    die("Error: Failed to load source image.\n");
}

// Preserve transparency
imagealphablending($sourceImage, false);
imagesavealpha($sourceImage, true);

// Generate app icons
echo "Generating app icons...\n";
foreach ($sizes as $size) {
    $iconPath = $iconsDir . "icon-{$size}x{$size}.png";
    echo "Generating $iconPath...\n";
    
    // Create a new image with the desired size
    $icon = imagecreatetruecolor($size, $size);
    
    // Preserve transparency
    imagealphablending($icon, false);
    imagesavealpha($icon, true);
    
    // Fill with transparent background
    $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
    imagefill($icon, 0, 0, $transparent);
    
    // Copy and resize the source image
    imagecopyresampled(
        $icon, $sourceImage,
        0, 0, 0, 0,
        $size, $size, imagesx($sourceImage), imagesy($sourceImage)
    );
    
    // Save the icon
    imagepng($icon, $iconPath);
    imagedestroy($icon);
}

// Generate special icons (menu, cart, etc.)
echo "Generating special icons...\n";
foreach ($specialIcons as $name => $size) {
    $iconPath = $iconsDir . "{$name}-{$size}x{$size}.png";
    echo "Generating $iconPath...\n";
    
    // Create a new image with the desired size
    $icon = imagecreatetruecolor($size, $size);
    
    // Preserve transparency
    imagealphablending($icon, false);
    imagesavealpha($icon, true);
    
    // Fill with transparent background
    $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
    imagefill($icon, 0, 0, $transparent);
    
    // Copy and resize the source image
    imagecopyresampled(
        $icon, $sourceImage,
        0, 0, 0, 0,
        $size, $size, imagesx($sourceImage), imagesy($sourceImage)
    );
    
    // Save the icon
    imagepng($icon, $iconPath);
    imagedestroy($icon);
}

// Generate favicon.ico (16x16)
$iconPath = $iconsDir . "favicon.ico";
echo "Generating $iconPath...\n";
$icon = imagecreatetruecolor(16, 16);
imagealphablending($icon, false);
imagesavealpha($icon, true);
$transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
imagefill($icon, 0, 0, $transparent);
imagecopyresampled($icon, $sourceImage, 0, 0, 0, 0, 16, 16, imagesx($sourceImage), imagesy($sourceImage));
imagepng($icon, $iconPath);
imagedestroy($icon);

// Generate apple-touch-icon
$iconPath = $iconsDir . "apple-icon-180x180.png";
echo "Generating $iconPath...\n";
$size = 180;
$icon = imagecreatetruecolor($size, $size);
imagealphablending($icon, false);
imagesavealpha($icon, true);
$transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
imagefill($icon, 0, 0, $transparent);
imagecopyresampled($icon, $sourceImage, 0, 0, 0, 0, $size, $size, imagesx($sourceImage), imagesy($sourceImage));
imagepng($icon, $iconPath);
imagedestroy($icon);

// Generate splash screens
echo "Generating splash screens...\n";
foreach ($splashSizes as $splashSize) {
    list($width, $height) = explode('x', $splashSize);
    $splashPath = $iconsDir . "splash-$splashSize.png";
    
    // Create splash screen canvas
    $splash = imagecreatetruecolor((int)$width, (int)$height);
    
    // Create background color (primary color from your theme)
    $bgColor = imagecolorallocate($splash, 255, 255, 255); // #FFF - primary color
    imagefill($splash, 0, 0, $bgColor);
    
    // Calculate icon size (40% of the smallest dimension)
    $iconSize = round(min((int)$width, (int)$height) * 0.4);
    
    // Calculate position (center)
    $x = round(((int)$width - $iconSize) / 2);
    $y = round(((int)$height - $iconSize) / 2);
    
    // Copy and resize the source image
    imagecopyresampled(
        $splash, $sourceImage,
        $x, $y, 0, 0,
        $iconSize, $iconSize, imagesx($sourceImage), imagesy($sourceImage)
    );
    
    // Add text "Fenyal" below the logo
    $white = imagecolorallocate($splash, 255, 255, 255);
    $fontSize = round($iconSize / 6);
    $font = __DIR__ . '/assets/fonts/Poppins-Bold.ttf'; // You might need to adjust this path
    
    // If font file doesn't exist, skip adding text
    if (file_exists($font)) {
        $textBounds = imagettfbbox($fontSize, 0, $font, "Fenyal");
        $textWidth = $textBounds[2] - $textBounds[0];
        $textX = ($width - $textWidth) / 2;
        $textY = $y + $iconSize + $fontSize * 2;
        
        imagettftext($splash, $fontSize, 0, $textX, $textY, $white, $font, "Fenyal");
    }
    
    // Save the splash screen
    imagepng($splash, $splashPath);
    imagedestroy($splash);
    
    echo "Generated $splashPath\n";
}

// Generate placeholder screenshots for manifest
echo "Generating placeholder screenshots for manifest...\n";
$screenshotSizes = ['750x1334'];

foreach ($screenshotSizes as $screenshotSize) {
    list($width, $height) = explode('x', $screenshotSize);
    
    // Create two placeholder screenshots
    for ($i = 1; $i <= 2; $i++) {
        $screenshotPath = $screenshotsDir . "screen{$i}.jpg";
        
        // Create screenshot canvas
        $screenshot = imagecreatetruecolor((int)$width, (int)$height);
        
        // Create background gradient-like effect
        $topColor = imagecolorallocate($screenshot, 196, 82, 48); // Primary color at top
        $bottomColor = imagecolorallocate($screenshot, 249, 109, 67); // Lighter version at bottom
        
        // Fill with gradient-like effect
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = 196 + (249 - 196) * $ratio;
            $g = 82 + (109 - 82) * $ratio;
            $b = 48 + (67 - 48) * $ratio;
            $lineColor = imagecolorallocate($screenshot, $r, $g, $b);
            imageline($screenshot, 0, $y, $width, $y, $lineColor);
        }
        
        // Copy and resize the source image (smaller for screenshot)
        $iconSize = round(min((int)$width, (int)$height) * 0.25);
        $x = round(((int)$width - $iconSize) / 2);
        $y = round(((int)$height - $iconSize) / 3);
        
        imagecopyresampled(
            $screenshot, $sourceImage,
            $x, $y, 0, 0,
            $iconSize, $iconSize, imagesx($sourceImage), imagesy($sourceImage)
        );
        
        // Add some UI elements to make it look like a screenshot
        $white = imagecolorallocate($screenshot, 255, 255, 255);
        $gray = imagecolorallocate($screenshot, 240, 240, 240);
        $darkGray = imagecolorallocate($screenshot, 100, 100, 100);
        
        // Draw a "card" in the middle
        imagefilledrectangle($screenshot, $width/6, $height/2, $width*5/6, $height*3/4, $white);
        imagefilledrectangle($screenshot, $width/6, $height/2, $width*5/6, $height/2 + 40, $gray);
        
        // Add some "text lines"
        for ($j = 1; $j <= 3; $j++) {
            imagefilledrectangle($screenshot, $width/6 + 20, $height/2 + 60 + $j*30, $width*5/6 - 20, $height/2 + 70 + $j*30, $gray);
        }
        
        // Add bottom navigation bar
        imagefilledrectangle($screenshot, 0, $height - 60, $width, $height, $white);
        
        // Add nav icons
        for ($j = 1; $j <= 5; $j++) {
            imagefilledrectangle($screenshot, $width*$j/6 - 15, $height - 40, $width*$j/6 + 15, $height - 25, $darkGray);
        }
        
        // Save as JPEG
        imagejpeg($screenshot, $screenshotPath, 90);
        imagedestroy($screenshot);
        
        echo "Generated $screenshotPath\n";
    }
}

// Cleanup
imagedestroy($sourceImage);

echo "\nAll icons, splash screens, and screenshots have been generated successfully!\n";
echo "Place a logo named 'logo.png' in the root directory or specify a path when running this script.\n";
echo "Example: php generate_icons.php /path/to/your/logo.png\n";
echo "\nPWA assets generated:\n";
echo "- App icons (various sizes): assets/icons/icon-*.png\n";
echo "- Special icons: assets/icons/menu-icon-96x96.png, assets/icons/cart-icon-96x96.png\n";
echo "- Favicon: assets/icons/favicon.ico\n";
echo "- Apple touch icon: assets/icons/apple-icon-180x180.png\n";
echo "- Splash screens: assets/icons/splash-*.png\n";
echo "- Manifest screenshots: assets/screenshots/screen*.jpg\n";