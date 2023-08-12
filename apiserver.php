<?php


include 'vendor/autoload.php';

use batdan\ai\MidjourneyImageCreator;

$discordChannelId = getenv('DISCORD_CHANNEL_ID');
$discordUserToken = getenv('USER_TOKEN');


// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');

    // Decode the JSON data
    $data = json_decode($input, true);

    // Check if JSON decoding was successful
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['prompt']) || !isset($data['tags'])) {
        header('HTTP/1.1 400 Bad Request');
        echo "Invalid or incomplete JSON input.";
        exit;
    }
    $midjourney = new MidjourneyImageCreator($discordChannelId, $discordUserToken);

    $message = $midjourney->imageCreationV2($data['prompt'], $data['tags']);
    $imgUrl  = $message->upscaled_photo_url;

    // Return the received message
    header('Content-Type: text/plain');
    echo $imgUrl;
} else {
    // Handle methods other than POST
    header('HTTP/1.1 405 Method Not Allowed');
    echo "Please use a POST request.";
}

?>