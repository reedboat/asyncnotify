<?php

namespace reedboat\AsyncNotify;

/**
 * Class QueueProtocol 
 * @author kufazhang
 */
interface QueueProtocol
{
    /*
     * @param string $channel
     * @return Message
     */
    public function pop($channel);

    /*
     * @param string $channel
     * @param Message $message
     */
    public function put($channel, Message $message);
}
