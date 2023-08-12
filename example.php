
<?php

include 'vendor/autoload.php';

use batdan\ai\MidjourneyImageCreator;

$discordChannelId = getenv('DISCORD_CHANNEL_ID');
$discordUserToken = getenv('USER_TOKEN');


$midjourney = new MidjourneyImageCreator($discordChannelId, $discordUserToken);

// Example of a prompt: text is separated from tags
$promptText = "aerial view of a giant fish tank shaped like a tower in the middle of new york city, https://depuismonhamac.jardiland.com/wp-content/uploads/2019/06/AdobeStock_196378179.jpeg";
$promptTags = "8k octane render, photorealistic --ar 9:20 --v 5";

/**
 * The imageCreationV2 method is responsible for randomly selecting an image from the 4 options provided by Midjourney.
 * If you want to specify a particular image, you can pass its identifier (ranging from 0 to 3) as the third parameter.
 * 
 * Example: $midjourneyImageCreator->imageCreation($promptText, $promptTags, 0);
 *
 * This will generate an image for the given prompt, using the specified image identifier (in this case, 0).
 */
$message = $midjourney->imageCreationV2($promptText, $promptTags);
$imgUrl  = $message->upscaled_photo_url;

echo chr(10) . chr(10);
echo $imgUrl;
echo chr(10) . chr(10);
