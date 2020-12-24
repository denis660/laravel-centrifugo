<?php

namespace denis660\Laracent\Contracts;

interface Centrifugo
{
    /**
     * Send message into channel.
     *
     * @param string $channel
     * @param array $data
     * @return mixed
     */
    public function publish(string $channel, array $data);

    /**
     * Send message into multiple channel.
     *
     * @param array $channels
     * @param array $data
     * @return mixed
     */
    public function broadcast(array $channels, array $data);

    /**
     * Get channel presence information (all clients currently subscribed to this channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function presence(string $channel);

    /**
     * Get channel presence information in short form (number of clients currently subscribed to this channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function presence_stats(string $channel);

    /**
     * Get channel history information (list of last messages sent into channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function history(string $channel);

    /**
     * Remove channel history information .
     *
     * @param string $channel
     * @return mixed
     */
    public function history_remove(string $channel);

    /**
     * Unsubscribe user from channel.
     *
     * @param string $channel
     * @param string $user
     * @return mixed
     */
    public function unsubscribe(string $channel, string $user);

    /**
     * Disconnect user by its ID.
     *
     * @param string $user_id
     * @return mixed
     */
    public function disconnect(string $user_id);

    /**
     * Get channels information (list of currently active channels).
     *
     * @return mixed
     */
    public function channels();

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
     * @param int $exp
     * @param string $info
     * @return string
     */
    public function generateConnectionToken(string $userId = '', int $exp = 0, array $info = []);

    /**
     * Generate private channel token.
     *
     * @param string $client
     * @param string $channel
     * @param int $exp
     * @param array $info
     * @return string
     */
    public function generatePrivateChannelToken(string $client, string $channel, int $exp = 0, array $info = []);
}
