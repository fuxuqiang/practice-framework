<?php

namespace Fuxuqiang\Framework\Http;

class HttpClient
{
    private $mh, $chs;

    public function __construct()
    {
        $this->mh = curl_multi_init();
    }

    /**
     * 获取批处理会话中的curl句柄
     */
    public function getHandles()
    {
        return $this->chs;
    }

    /**
     * 批量发送请求
     */
    public function multiRequest()
    {
        $active = null;
        do {
            while (curl_multi_exec($this->mh, $active) != CURLM_OK);
            if (curl_multi_select($this->mh) == -1) {
                return yield from array_map(fn($ch) => $this->removeHandle($ch->handle), $this->chs);
            }
            while ($info = curl_multi_info_read($this->mh)) {
                yield $this->removeHandle($info['handle']);
            }
        } while ($active);
    }

    /**
     * 移除批处理会话中的curl句柄
     */
    private function removeHandle($ch)
    {
        curl_multi_remove_handle($this->mh, $ch);
        $ch = $this->chs[$id = (int) $ch];
        unset($this->chs[$id]);
        return $ch;
    }

    /**
     * 向批处理会话中添加curl句柄
     */
    public function addHandle($url, $params = [], $opt = [], $method = 'GET')
    {
        curl_multi_add_handle($this->mh, $ch = self::getHandle($url, $params, $opt, $method));
        $this->chs[(int) $ch] = new CurlHandle($ch, $params);
    }

    /**
     * 发送请求
     */
    public static function request($url, $params, $opts = [], $method = 'POST')
    {
        $ch = self::getHandle($url, $params, $opts, $method);
        $content = curl_exec($ch);
        if (($code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) != 200) {
            throw new \Exception($content, $code);
        }
        return $content;
    }

    /**
     * 获取curl句柄
     */
    private static function getHandle($url, $params, $opts, $method)
    {
        if ($method == 'POST') {
            $opts += [CURLOPT_POSTFIELDS => $params];
        }
        curl_setopt_array($ch = curl_init($url), $opts + [CURLOPT_RETURNTRANSFER => true]);
        return $ch;
    }
}
