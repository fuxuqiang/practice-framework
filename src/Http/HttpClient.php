<?php

namespace Fuxuqiang\Framework\Http;

use Exception;

class HttpClient
{
    private \CurlMultiHandle $mh;

    private array $chs;

    public function __construct()
    {
        $this->mh = curl_multi_init();
    }

    /**
     * 获取批处理会话中的curl句柄
     */
    public function getHandles(): array
    {
        return $this->chs;
    }

    /**
     * 批量发送请求
     */
    public function multiRequest($timeout = 30, $interval = 0)
    {
        $start = time();
        $active = 0;
        do {
            while (curl_multi_exec($this->mh, $active) != CURLM_OK);
            if (curl_multi_select($this->mh) == -1 || time() - $start > $timeout) {
                foreach ($this->chs as $ch) {
                    yield $this->removeHandle($ch->handle);
                }
                return;
            }
            sleep($interval);
            while ($info = curl_multi_info_read($this->mh)) {
                $start = time();
                yield $this->removeHandle($info['handle']);
            }
        } while ($this->chs);
    }

    /**
     * 向批处理会话中添加curl句柄
     */
    public function addHandle(string $url, array $params = [], array $opt = [], string $method = 'GET'): void
    {
        curl_multi_add_handle($this->mh, $ch = self::getHandle($url, $params, $opt, $method));
        $this->chs[(int) $ch] = new CurlHandle($ch, $params);
    }

    /**
     * 移除批处理会话中的curl句柄
     */
    public function removeHandle($ch): CurlHandle
    {
        curl_multi_remove_handle($this->mh, $ch);
        $id = (int) $ch;
        $ch = $this->chs[$id];
        unset($this->chs[$id]);
        return $ch;
    }

    /**
     * 发送请求
     * @throws Exception
     */
    public static function request($url, $params, $opts = [], $method = 'POST'): bool|string
    {
        $ch = self::getHandle($url, $params, $opts, $method);
        $content = curl_exec($ch);
        if (($code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) != 200) {
            throw new Exception($content, $code);
        }
        return $content;
    }

    /**
     * 获取curl句柄
     */
    private static function getHandle($url, $params, $opts, $method): \CurlHandle|bool
    {
        if ($method == 'POST') {
            $opts += [CURLOPT_POSTFIELDS => $params];
        }
        curl_setopt_array($ch = curl_init($url), $opts + [CURLOPT_RETURNTRANSFER => true]);
        return $ch;
    }
}
