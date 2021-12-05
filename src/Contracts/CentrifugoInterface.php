<?php

declare(strict_types=1);

namespace denis660\Centrifugo\Contracts;

interface CentrifugoInterface
{
    /**
     * Send message into channel.
     *
     * @param string $channel
     * @param array  $data
     * @param bool   $skipHistory (optional)
     *
     * @return mixed
     */
    public function publish(string $channel, array $data, $skipHistory = false);

    /**
     * Send message into multiple channel.
     *
     * @param array $channels
     * @param array $data
     * @param bool  $skipHistory (optional)
     *
     * @return mixed
     */
    public function broadcast(array $channels, array $data, $skipHistory = false);

    /**
     * Get channel presence information (all clients currently subscribed to this channel).
     *
     * @param string $channel
     *
     * @return mixed
     */
    public function presence(string $channel);

    /**
     * Get channel presence information in short form (number of clients currently subscribed to this channel).
     *
     * @param string $channel
     *
     * @return mixed
     */
    public function presenceStats(string $channel);

    /**
     * Get channel history information (list of last messages sent into channel).
     *
     * @param string $channel
     * @param int    $limit   (optional)
     * @param array  $since   (optional)
     * @param bool   $reverse (optional)
     *
     * @return mixed
     */
    public function history(string $channel, $limit = 0, $since = [], $reverse = false);

    /**
     * Remove channel history information .
     *
     * @param string $channel
     *
     * @return mixed
     */
    public function historyRemove(string $channel);

    /**
     * Subscribe user to channel.
     *
     * @param string $channel
     * @param string $user
     * @param string $client  (optional)
     *
     * @return mixed
     */
    public function subscribe($channel, $user, $client = '');

    /**
     * Unsubscribe user from channel.
     *
     * @param string $channel
     * @param string $user
     * @param string $client  (optional)
     *
     * @return mixed
     */
    public function unsubscribe(string $channel, string $user, string $client = '');

    /**
     * Disconnect user by its ID.
     *
     * @param string $user_id
     *
     * @return mixed
     */
    public function disconnect(string $user_id, string $client = '');

    /**
     * Get channels information (list of currently active channels).
     *
     * @param string $pattern (optional)
     *
     * @return mixed
     */
    public function channels(string $pattern = '');

    /**
     * Get stats information about running server nodes.
     *
     * @return mixed
     */
    public function info();

    /**
     * Generate connection token.
     *
     * @param string $userId
     * @param int    $exp
     * @param array  $info
     * @param array  $channels
     *
     * @return string
     */
    public function generateConnectionToken(string $userId = '', int $exp = 0, array $info = [], array $channels = []);

    /**
     * Generate private channel token.
     *
     * @param string $client
     * @param string $channel
     * @param int    $exp
     * @param array  $info
     *
     * @return string
     */
    public function generatePrivateChannelToken(string $client, string $channel, int $exp = 0, array $info = []);
}
