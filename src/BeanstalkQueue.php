<?php
namespace reedboat\AsyncNotify;

class BeanstalkQueue implements QueueProtocol
{

    private $beanstalk;

    const CHANNEL_PREFIX = 'notify_channel_';

    public function __construct($backend=null){
        $this->beanstalk = $backend;
    }

    /**
     * 取出最新消息
     * @param string $channel
     * @return Message
     */
    public function pop($channel)
    {
        if (!$this->beanstalk){
            throw new RuntinmeException("no backend for notify queue");
        }
        $tube = self::CHANNEL_PREFIX . $channel;
        $job = $this->beanstalk->reserveFromTube($tube);
        if ($job){
            $message = new Message;
            $message->id = $job->getId();
            return $message->loads($job->getData());
        }
    }

    /**
     * 压入最新消息
     *
     * @param string $channel
     * @param Message $message
     *
     * @return void
     */
    public function put($channel, Message $message)
    {
        if (!$this->beanstalk){
            throw new RuntinmeException("no backend for notify queue");
        }
        $tube = self::CHANNEL_PREFIX . $channel;
        $this->beanstalk->putInTube($tube, $message->dumps());
    }


    /*
     * 从取出状态到, 完全移除
     *
     * @param string $channel
     * @param object $message Message
     */
    public function del($channel, $message){
        if (!$this->beanstalk){
            throw new RuntinmeException("no backend for notify queue");
        }
        $job = new Pheanstalk\Job($message->id, null);
        $this->beanstalk->delete($job);
    }

    public function setBackend(Pheanstalk\Pheanstalk $beanstalk) 
    {
        $this->beanstalk = $beanstalk;
    }
}
