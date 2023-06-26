<?php

namespace batdan\ai;

use GuzzleHttp\Client;

/**
 * Class allowing to create an image from a prompt and to retrieve the link of the image
 * 
 * Documentation Discord API : https://discord.com/developers/docs/interactions/application-commands
 */
class MidjourneyImageCreator
{

    private $apiUrl = 'https://discord.com/api/v10';    // Discord API URL
    private $applicationId  = '936929561302675456';     // Unique ID for the application

    private $dataId;            // Unique ID for the command
    private $dataVersion;       // Unique Version for the command

    private $sessionId;         // Unique ID for the session
    private $client;            // GuzzleHttp\Client
    private $channelId;         // Discord Channel ID
    private $oauthToken;        // Discord OAuth Token User
    private $guildId;           // Discord Guild ID
    private $userId;            // Discord User ID

    private $uniqueId;          // Unique ID for the prompt


    /**
     * Constructor
     *
     * @param string $discordChannelId
     * @param string $discordUserToken
     */
    public function __construct($discordChannelId, $discordUserToken)
    {
        $this->sessionId = md5(uniqid());

        $this->channelId = $discordChannelId;
        $this->oauthToken = $discordUserToken;

        // Création du client GuzzleHttp
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => $this->oauthToken
            ]
        ]);

        // Guild Id recovery
        $response = $this->client->get('channels/' . $this->channelId);
        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        $this->guildId = $json['guild_id'] ?? null;

        // User id recovery
        $response = $this->client->get('users/@me');
        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        $this->userId = $json['id'];

        // Retrieval of dataId and dataVersion
        $response = $this->client->get('applications/' . $this->applicationId . '/commands');
        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        $this->dataId       = $json[0]['id'];
        $this->dataVersion  = $json[0]['version'];
    }


    /**
     * Global method to recover an image
     *
     * @param   string      $prompt             Midjourney prompt
     * @param   integer     $upscale_index      Choice of image to upscale - default: random 0.3
     * @return  object
     */
    public function imageCreation($prompt, $upscale_index = null)
    {
        // Random image selection if $upscale_index is null
        if (is_null($upscale_index)) $upscale_index = rand(0, 3);

        $imagine = $this->getImagine($prompt);
        $upscaled_photo_url = $this->getUpscale($imagine, $upscale_index);

        return (object) [
            'imagine_message_id' => $imagine['id'],
            'upscaled_photo_url' => $upscaled_photo_url
        ];
    }


    /**
     * Call /imagine
     *
     * @param   string      $prompt     Prompt midjourney
     * @return  void
     */
    public function getImagine(string $prompt)
    {
        $this->uniqueId = time() - rand(0, 1000);
        $promptWithId = $prompt . ' --seed ' . $this->uniqueId;

        $params = [
            'type'              => 2,
            'application_id'    => $this->applicationId,
            'guild_id'          => $this->guildId,
            'channel_id'        => $this->channelId,
            'session_id'        => $this->sessionId,
            'data' => [
                'id'        => $this->dataId,
                'version'   => $this->dataVersion,
                'name'      => 'imagine',
                'type'      => 1,
                'options'   => [
                    [
                        'type'  => 3,
                        'name'  => 'prompt',
                        'value' => $promptWithId
                    ],
                ],
            ],
        ];

        if (is_null($this->guildId)) {
            unset($params['guild_id']);
        }

        $this->client->post('interactions', [
            'json' => $params
        ]);

        sleep(8);

        $imagine_message = null;

        // Max time loop: just over 5 minutes of waiting
        $maxLoop = 40;

        while (is_null($imagine_message)) {
            $maxLoop--;
            if ($maxLoop == 0) break;

            $imagine_message = $this->checkImagine();
            if (is_null($imagine_message)) sleep(8);
        }

        return $imagine_message;
    }


    /**
     * Method to retrieve the Midjourney message and identify when the 4 visuals are ready
     * 
     * @return void
     */
    public function checkImagine()
    {
        $response = $this->client->get('channels/' . $this->channelId . '/messages');
        $response = $response->getBody()->getContents();
        $items = json_decode($response, true);

        $raw_message = null;

        foreach ($items as $item) {
            if (
                str_contains($item['content'], $this->uniqueId) &&
                str_contains($item['content'], '<@' . $this->userId . '> (fast)')
            ) {
                $raw_message = $item;
                break;
            }

            if (is_null($raw_message)) {
                if (
                    str_contains($item['content'], $this->uniqueId) &&
                    str_contains($item['content'], '<@' . $this->userId . '> (Open on website for full quality) (fast)')
                ) {
                    $raw_message = $item;
                    break;
                }
            }
        }

        if (is_null($raw_message)) return null;

        return [
            'id'            => $raw_message['id'],
            'raw_message'   => $raw_message
        ];
    }


    /**
     * Method to upscale an image of Midjourney among the 4 proposed
     * 
     * @param   array   $message            Array returned by the getImagine method
     * @param   integer $upscale_index      Choice of image to upscale (0.3)
     * @return  void
     */
    public function getUpscale($message, int $upscale_index)
    {
        if (!isset($message['raw_message'])) {
            error_log('Upscale requires a message object obtained from the imagine/getImagine methods.');
        }

        if ($upscale_index < 0 or $upscale_index > 3) {
            error_log('Upscale index must be between 0 and 3.');
        }

        $upscale_hash = null;
        $raw_message = $message['raw_message'];

        if (isset($raw_message['components']) && is_array($raw_message['components'])) {
            $upscales = $raw_message['components'][0]['components'];
            $upscale_hash = $upscales[$upscale_index]['custom_id'];
        }

        $params = [
            'type'              => 3,
            'guild_id'          => $this->guildId,
            'channel_id'        => $this->channelId,
            'message_flags'     => 0,
            'message_id'        => $message['id'],
            'application_id'    => $this->applicationId,
            'session_id'        => $this->sessionId,
            'data' => [
                'component_type' => 2,
                'custom_id'     => $upscale_hash
            ]
        ];

        if (is_null($this->guildId)) {
            unset($params['guild_id']);
        }

        $this->client->post('interactions', [
            'json' => $params
        ]);

        $upscaled_photo_url = null;

        // Max time loop: 3 minutes
        $maxLoop = 60;

        while (is_null($upscaled_photo_url)) {
            $maxLoop--;
            if ($maxLoop == 0) break;

            $upscaled_photo_url = $this->checkUpscale($message, $upscale_index);
            if (is_null($upscaled_photo_url)) sleep(3);
        }

        return $upscaled_photo_url;
    }


    /**
     * Method to check if the upscaled image is ready
     * 
     * @param   array   $message            Array returned by the getImagine method
     * @param   integer $upscale_index      Choice of image to upscale (0.3)
     * @return  void
     */
    public function checkUpscale($message, $upscale_index = 0)
    {
        if (!isset($message['raw_message'])) {
            error_log('Upscale requires a message object obtained from the imagine/getImagine methods.');
        }

        if ($upscale_index < 0 || $upscale_index > 3) {
            error_log('Upscale index must be between 0 and 3.');
        }

        $response = $this->client->get('channels/' . $this->channelId . '/messages');
        $response = $response->getBody()->getContents();
        $items = json_decode($response, true);

        $message_index = $upscale_index + 1;
        $message = null;

        foreach ($items as $item) {
            if (
                str_contains($item['content'], $this->uniqueId) &&
                str_contains($item['content'], "Image #{$message_index} <@{$this->userId}>")
            ) {
                $message = $item;
                break;
            }

            if (is_null($message)) {
                if (
                    str_contains($item['content'], $this->uniqueId) &&
                    str_contains($item['content'], "Upscaled by <@{$this->userId}> (fast)")
                ) {
                    $message = $item;
                    break;
                }
            }
        }

        return (!is_null($message) && isset($message['attachments'])) ? $message['attachments'][0]['url'] : null;
    }
}
