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
     * @return array
     */
    public function publish(string $channel, array $data, bool $skipHistory = false);

    /**
     * Send message into multiple channel.
     *
     * @param array $channels
     * @param array $data
     * @param bool  $skipHistory (optional)
     *
     * @return array
     */
    public function broadcast(array $channels, array $data, bool $skipHistory = false);

    /**
     * Get channel presence information (all clients currently subscribed to this channel).
     *
     * @param string $channel
     *
     * @return array
     */
    public function presence(string $channel);

    /**
     * Get channel presence information in short form (number of clients currently subscribed to this channel).
     *
     * @param string $channel
     *
     * @return array
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
     * @return array
     */
    public function history(string $channel, int $limit = 0, array $since = [], bool $reverse = false);

    /**
     * Remove channel history information .
     *
     * @param string $channel
     *
     * @return array
     */
    public function historyRemove(string $channel);

    /**
     * Subscribe user to channel.
     *
     * @param string $channel
     * @param string $user
     * @param string $client  (optional)
     *
     * @return array
     */
    public function subscribe(string $channel, string $user, string $client = '');

    /**
     * Unsubscribe user from channel.
     *
     * @param string $channel
     * @param string $user
     * @param string $client  (optional)
     *
     * @return array
     */
    public function unsubscribe(string $channel, string $user, string $client = '');

    /**
     * Disconnect user by its ID.
     *
     * @param string $userId
     * @param string $client
     *
     * @return array
     */
    public function disconnect(string $userId, string $client = '');

    /**
     * Get channels information (list of currently active channels).
     *
     * @param string $pattern (optional)
     *
     * @return array
     */
    public function channels(string $pattern = '');

    /**
     * Get stats information about running server nodes.
     *
     * @return array
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
     * @param string $userId
     * @param string $channel
     * @param int    $exp
     * @param array  $info
     *
     * @return string
     */
    public function generatePrivateChannelToken(string $userId, string $channel, int $exp = 0, array $info = []);
}
