<?php
namespace reedboat\AsyncNotify

class Message {

    public function __construct($content, $scope){
        $this->timestamp = time();
        $this->content = $content;
        $this->scope = $scope;
        $this->id    = 0;
    }

    /*
     * @string message id
     */
    public $id;

    /**
     * 消息的产生范围
     *
     * @map
     */
    public $scope;

    /**
     * 消息的产生时间
     *
     * @type int
     */
    public $timestamp;

    /**
     * 消息的数据
     *
     * @mixed
     */
    public $content;


    public function loads($content) 
    {
        $data = json_decode($content, true);
        $this->id        = $data['id'];
        $this->scope     = $data['scope'];
        $this->timestamp = $data['timestamp'];
        $this->content   = $data['content'];

        return $this;
    }

    public function dumps()
    {
        return json_encode(array(
            'id'        => $this->id,
            'scope'     => $this->scope,
            'timestamp' => $this->timestamp,
            'content'   => $this->content,
            ));
    }
}

