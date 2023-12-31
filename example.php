<?php

include 'vendor/autoload.php';

use batdan\ai\MidjourneyImageCreator;

/**
 * For retrieving the channel ID and the User Token,
 * please refer to the instructions detailed in the README.md file.
 */
$discordChannelId = 'YOUR_DISCORD_CHANNEL_ID';
$discordUserToken = 'YOUR_DISCORD_USER_TOKEN';

$midjourney = new MidjourneyImageCreator($discordChannelId, $discordUserToken);

// Example of a prompt: text is separated from tags
$promptText = "aerial view of a giant fish tank shaped like a tower in the middle of new york city, https://depuismonhamac.jardiland.com/wp-content/uploads/2019/06/AdobeStock_196378179.jpeg";
$promptTags = "8k octane render, photorealistic --ar 9:20 --v 5";

// The process of generating and upscaling an image typically takes approximately one minute.
$message = $midjourney->imageCreationV2($promptText, $promptTags);
$imgUrl  = $message->upscaled_photo_url;

echo chr(10);
echo chr(10);
echo $imgUrl;
echo chr(10);
echo chr(10);
