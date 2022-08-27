<?php

namespace Fuxuqiang\Framework\Http;

class HttpClient
{
    private $mh, $chs;

    public function __construct($multi = false)
    {
        $multi && $this->mh = curl_multi_init();
    }

    /**
     * 获取curl句柄
     */
    public function getChs()
    {
        return $this->chs;
    }

    /**
     * 发送请求
     */
    public function multiRequest($timeout = 1)
    {
        $active = null;
        do {
            while (curl_multi_exec($this->mh, $active) === CURLM_CALL_MULTI_PERFORM);
            curl_multi_select($this->mh, $timeout);
            while ($info = curl_multi_info_read($this->mh)) {
                curl_multi_remove_handle($this->mh, $info['handle']);
                $ch = $this->chs[$id = (int) $info['handle']];
                unset($this->chs[$id]);
                yield $ch;
            }
        } while ($active);
    }

    /**
     * 向curl批处理会话中添加单独的curl句柄
     */
    public function addHandle($url, $params = [], $opt = [], $method = 'GET')
    {
        curl_multi_add_handle($this->mh, $ch = $this->getHandle($url, $params, $opt, $method));
        $this->chs[(int) $ch] = new CurlHandle($ch, $params);
    }

    /**
     * 执行curl会话
     */
    public function request($url, $params, $opts = [], $method = 'POST')
    {
        $ch = $this->getHandle($url, $params, $opts, $method);
        $content = curl_exec($ch);
        if (($code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) != 200) {
            throw new \Exception($content, $code);
        }
        return $content;
    }

    /**
     * 获取curl句柄
     */
    private function getHandle($url, $params, $opts, $method)
    {
        $ch = curl_init($url);
        $method == 'POST' && $opts += [CURLOPT_POSTFIELDS => $params];
        curl_setopt_array($ch, $opts + [CURLOPT_RETURNTRANSFER => true]);
        return $ch;
    }
}
