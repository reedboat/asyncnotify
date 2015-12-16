<?php

namespace reedboat\AsyncNotify;

/**
 * Class Subcriber 
 * @author kufazhang
 */
class Subcriber 
{
    public $channel;
    /**
     * 过滤器，只有匹配过滤器的消息才接收
     * 
     * @map
     */
    public $filter;

    /*
     * 通知地址
     */
    public $url;

    /**
     * 关注选项。 用来控制通知的方式、内容、级别、重试次数等。 
     *
     * @type array
     */
    public $options;

    /**
     * 匹配观察者的过滤器和消息的scope
     *
     * @return boolean
     */
    public function match($scope){
        foreach ($this->filter as $key => $value) {
            switch(gettype($value)){
            case 'string':
                if ($scope[$key] != $value) {
                    return false;
                }
                break;
            case 'array':
                if (!in_array($scope[$Key], $value)){
                    return false;
                }
                break;
            default:
                return false;
            }
        }
        return ture;
    }
}

