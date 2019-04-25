<?php
namespace TwichIntegration\Components;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChatTopic extends Eole\Sandstone\Websocket\Topic implements EventSubscriberInterface
{
    /**
     * Broadcast message to each subscribing client.
     *
     * {@InheritDoc}
     */
    public function onPublish(Ratchet\Wamp\WampConnection $conn, $topic, $event)
    {
        $this->broadcast([
            'type' => 'message',
            'message' => $event,
        ]);
    }

    /**
     * Subscribe to article.created event.
     *
     * {@InheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'user.followed' => 'onUserFollowed',
        ];
    }

    public function onUserFollowed($event) {
        $this->broadcast([
            'type' => 'user_followed',
            'message' => 'A new user has followed'
        ])
    }
}
