<?php
namespace TwichIntegration\Components;

use GuzzleHttp\Client;

class TwichAPI
{
    private $client;
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct()
    {
        $this->client = new Client();
        $this->clientId = getenv('TWICH_CLIENT_ID');
        $this->clientSecret = getenv('TWICH_CLIENT_SECRET');
        $this->redirectUri = getenv('TWICH_REDIRECT_URI');
    }

    public function getUser($code)
    {
        $uri = 'https://id.twitch.tv/oauth2/token' . $this->_fetchTwichParams() . 'grant_type=authorization_code&code=' . $code;
        $response = $this->client->post($uri, []);
        return $this->_transformResponse($response);
    }

    public function getStreamByUsername($access_token, $username)
    {
        $uri = 'https://api.twitch.tv/helix/streams?user_login='.$username;
        $response = $this->client->get($uri, ['headers' => [
            'Authorization' => 'Bearer ' . $access_token,
        ]]);
        return $this->_transformResponse($response);
    }

    public function getStreamer($access_token, $name)
    {
        $uri = 'https://api.twitch.tv/helix/users?login=' . $name;
        $response = $this->client->get($uri, ['headers' => [
            'Authorization' => 'Bearer ' . $access_token,
        ]]);
        return $this->_transformResponse($response);
    }

    public function subscribeForEvents($userId)
    {
        $uri = 'https://api.twitch.tv/helix/webhooks/hub';
        $params = [
            'hub.mode' => 'subscribe',
            'hub.topic' => 'https://api.twitch.tv/helix/users/follows?first=1&to_id='.$userId,
            'hub.callback' => $this->redirectUri.'/subscribe',
            'hub.lease_seconds' => '864000',
            'hub.secret' => 's2sar'
        ];

        $response = $this->client->post($uri, $params);

        $params['hub.topic'] = 'https://api.twitch.tv/helix/streams?user_id='.$userId;
        $response = $this->client->post($uri, $params);

        $params['hub.topic'] = 'https://api.twitch.tv/helix/users?id='.$userId;
        return true;
    }

    private function _fetchTwichParams($clientId = true, $clientSecret = true, $redirectUri = true)
    {
        $url = '?';
        if ($clientId) {
            $url .= 'client_id=' . $this->clientId . '&';
        }
        if ($clientSecret) {
            $url .= 'client_secret=' . $this->clientSecret . '&';
        }
        if ($redirectUri) {
            $url .= 'redirect_uri=' . $this->redirectUri . '&';
        }

        return $url;
    }

    private function _transformResponse($response)
    {
        return json_decode((string) $response->getBody(), true);
    }
}
