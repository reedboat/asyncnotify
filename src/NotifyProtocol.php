<?php

namespace reedboat\AsyncNotify;

/**
 * Class NotifyProtocol
 * @author kufazhang
 */
interface NotifyProtocol
{
    public function subcribe($channel, $url, $filter, $options); //订阅
    public function broadcast($message); //广播
}
