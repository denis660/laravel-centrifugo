<?php

declare(strict_types=1);

namespace denis660\Centrifugo;

/**
 * Parse and process channel names to and from Centrifugo server.
 */
class Channel
{
    /**
     * @var Contracts\CentrifugoInterface
     */
    protected $centrifugo;

    /**
     * The original channel name when instantiated.
     *
     * @var string
     */
    protected $orig;

    /**
     * The 'bare' channel name, with no modifiers and no namespace.
     *
     * @var string
     */
    protected $name;

    /**
     * Channel private state.
     *
     * @var bool
     */
    protected $private;

    /**
     * The centrifugo namespace.
     *
     * @see https://centrifugal.dev/docs/server/channels#channel-namespaces
     *
     * @var string
     */
    protected $namespace;

    public function __construct(Centrifugo $centrifugo, $channel)
    {
        $this->orig = $channel;
        $this->private = substr($channel, 0, 1) === '$';
        $this->name = $this->private ? substr($channel, 1) : $channel;
        $this->centrifugo = $centrifugo;
        $this->namespace = $centrifugo->getNamespace();
        if ($this->namespace) {
            $parts = explode(':', $this->name);
            if (count($parts) > 1 && $parts[0] === $this->namespace) {
                $this->name = substr($this->name, strlen($this->namespace) + 1);
            }
        }
    }

    /**
     * Get channel private status.
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * Get the plain channel name, with no modifiers.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the channel name as used internally.
     *
     * @return string
     */
    public function getInternalName()
    {
        return ($this->isPrivate() ? '$' : '').$this->name;
    }

    /**
     * Return complete channel name sent to centrifugo server.
     *
     * @return string
     */
    public function getCentrifugoName()
    {
        $privateStr = $this->isPrivate() ? '$' : '';
        $namespaceStr = $this->namespace ? "$this->namespace:" : '';
        return $privateStr.$namespaceStr.$this->getName();
    }
}
