<?php
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ResponseException;

namespace reedboat\AsyncNotify;

/**
 * Class AsyncNotifyService
 * @author kufazhang
 */
class AsyncNotifyService
{
    /**
     * 最大子进程数
     */
    public $maxProcesses =  32;

    /**
     * 当前执行的jobs
     */
    public $runningJobs  = array();

    /**
     * 订阅者列表
     */
    public $subcribers = array();

    /**
     * Http请求客户端
     */
    public $httpClient = null;

    private $freeInterval = 1;
    private $workInterval = 0.1;

    //public function __construct(){
    //    pcntl_signal(SIGCHLD, array(&$this, childSignalHandler));
    //}

    public function setHttpClient(GuzzleHttp\ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * 通知所有的观察者
     *
     * @return void
     */
    public function broadcast(NotificationMessage $message)
    {
            //if (count($this->runningJobs) > $this->maxProcess){
            //    usleep($this->freeInterval * 1000000);
            //    continue;
            //}

        $subcribers = $this->getSubcribers($message->channel);
        $childrenCount = 0;
        foreach($subcribers as $subcriber){
            if ($subcriber->match($message->scope)){
                $pid = pcntl_fork();
                if ($pid < 0){
                    //todo logfailed();
                } else if ($pid > 0){
                    //父进程
                    $this->runningJobs[$pid] = $message->data;
                    $childrenCount ++;
                } else {
                    //子进程
                    $exitCode = $this->notifySubcriber($message, $subcriber);
                    exit($exitCode);
                }
            }
        }

        //reap child processes
        while($childrenCount > 0){
            $pid = pcntl_wait($status);
            if ($pid > 0){
                $childrenCount -- ;
            }
        }
        //usleep($this->workInterval * 1000000);
    }

    public function getSubcribers($channel){
        return $this->subcriber[$channel];
    }


    /**
     * 通知观察者
     *
     * @return void
     */
    public function notifySubcriber($message, $subcriber)
    {
        $options = new OptionHelper($subcriber->options);

        $url     = $subcriber->url;
        $method  = $options->get('method', 'GET');
        $headers = $options->get('headers', array());
        if (is_array($message->data)){
            $body = http_build_query($message->data);
        }else {
            $body = $message->data;
        }


        $request = new Request($method, $subcriber->url, $headers, $body);

        $retryMaxTimes = $options->get('retry_max_times', 1);
        $retryInterval  = $options->get('retry_interval', 5);
        $contentType    = $options->get('content_type', 'json');
        $times = 0;
        $exitCode = 1;
        while ($times++ < $maxRetryTimes){
            if ($this->isSucced($response, $options)){
                $exitCode = 0;
                break;
            }
            sleep($retryInterval);
        }

        return $exitCode;
    }

    private function isSucced($response, $options){
        $response = $this->client->send($request);
        $body     = $response->getBody();

        $options = new OptionHelper($subcriber->options);
        $contentType    = $options->get('content_type', 'json');
        switch($contentType){
        case 'jsonp':
            $body = $this->filterJsonp($body);
            //passthrough
        case 'json':
            $retdata = json_decode($data, true);
            $code_field = $options->get('code_field', 'code');
            $code = $this->extract($retdata, $code_field);
            if ($code == 0){
                return true;
            }
            break;
        default:
            return $body == "success";
        }
        return false;
    }

    /**
     * 订阅
     *
     * 根据参数 自动创建订阅者并加入到订阅列表中。 
     * @return void
     */
    public function subscribe($channel, $url, $filter=array(), $options=array())
    {
        $subcriber = new subcriber;
        $subcriber->channel = $channel;
        $subcriber->url     = $url;
        $subcriber->filter  = $filter;
        $subcriber->options = $options;

        $this->addsubcriber($channel, $subcriber);
    }

    /**
     * 添加订阅者
     *
     * @param string $channel
     * @param NotificationScriber $subcriber
     *
     * @return void
     */
    public function addSubcriber($channel, $subcriber){
        if (empty($this->_subcribers[$channel])){
            $this->_subcribers[$channel] = array();
        }
        $this->_subcribers[$channel][] = $subcriber;
    }


    /*
     * 收割子进程
     *
     */
    //public function signalHandler($signo, $pid = null, $status = null)
    //{
    //    if ($signo != SIGCHLD){
    //        return;
    //    }
    //    if (!$pid){
    //        $pid = pcntl_waitpid(-1, $status, WNOHANG);
    //    }
    //    while($pid > 0){
    //        if ($pid && isset($this->currentJobs[$pid])){
    //            $exitCode = pcntl_wexitstatus($status);
    //            if ($exitCode != 0) {
    //                echo "$pid exited with status ", $exitCode . "\n";
    //            }
    //            unset($this->currentJobs[$pid]);
    //        } elseif ($pid){
    //            //$this->signalQueue[$pid] = $status;
    //        }
    //        $pid = pcntl_waitpid(-1, $status, WNOHANG);
    //    }
    //    return true;
    //}

    private function filterJsonp($content){
        $json = $content;
        $json = str_replace(array("\n","\r"),"",$json);
        $json = trim($json);
        $json = preg_replace("/^\w+\(|\);?/", "", $json);
        $json = preg_replace("/^(var)?\s*\w+\s*=\s*|;$/", "", $json);
        $json = mb_convert_encoding($json, "utf-8", "utf-8, gb18030, gbk, gb2312");
        return $json;
    }

    private function extract($data, $key){
        $levels = preg_split('/[\.\/]/', $key);
        foreach($levels as $level){
            if (isset($data[$level])){
                $data = $data[$level];
            }
            else {
                return null;
            }
        }
        return $data;
    }
}

