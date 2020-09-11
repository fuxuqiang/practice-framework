<?php

namespace Fuxuqiang\Framework;

class JWT
{
    private $header = ['typ' => 'JWT', 'alg' => 'HS256'], $exp, $key;

    public function __construct($exp, $key)
    {
        $this->exp = $exp;
        $this->key = $key;
    }

    /**
     * 生成token
     */
    public function encode($sub, $jti = '')
    {
        return ($data = $this->base64Encode(json_encode($this->header)) . '.'
            . $this->base64Encode(json_encode(['sub' => $sub, 'exp' => time() + $this->exp, 'jti' => $jti])))
            . '.' . $this->base64Encode($this->sign($data));
    }

    /**
     * 解码token
     */
    public function decode($token)
    {
        $data = explode('.', $token);
        if (count($data) != 3) {
            throw new \Exception('token格式错误');
        }
        if (
            ($header = json_decode($this->base64Decode($data[0]))) && $header->alg == $this->header['alg']
            && ($payload = json_decode($this->base64Decode($data[1]))) && $payload->exp > time()
            && hash_equals($this->sign($data[0] . '.' . $data[1]), $this->base64Decode($data[2]))
        ) {
            return $payload;
        }
    }

    /**
     * 通过base64_encode()生成可在url中安全传输的编码
     */
    private function base64Encode($data)
    {
        return str_replace('=', '', strtr(base64_encode($data), '+/', '-_'));
    }

    /**
     * 通过base64_decode()解码经过url安全处理的编码
     */
    private function base64Decode($data)
    {
        ($remainder = strlen($data) % 4) && $data .= str_repeat('=', 4 - $remainder);
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * 签名
     */
    private function sign($data)
    {
        return hash_hmac('sha256', $data, $this->key, true);
    }
}
