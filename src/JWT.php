<?php

namespace Fuxuqiang\Framework;

use Exception;

class JWT
{
    private array $header = ['typ' => 'JWT', 'alg' => 'HS256'];

    public function __construct(private readonly int $exp, private readonly string $key) {}

    /**
     * 生成token
     */
    public function encode($sub, $jti = ''): string
    {
        $data = $this->base64Encode(json_encode($this->header)) . '.'
            . $this->base64Encode(json_encode(['sub' => $sub, 'exp' => time() + $this->exp, 'jti' => $jti]));
        return $data . '.' . $this->base64Encode($this->sign($data));
    }

    /**
     * 解码token
     * @throws Exception
     */
    public function decode($token)
    {
        $data = explode('.', $token);
        if (count($data) != 3) {
            throw new Exception('token格式错误');
        }
        $header = json_decode($this->base64Decode($data[0]));
        if ($header && $header->alg == $this->header['alg']) {
            $payload = json_decode($this->base64Decode($data[1]));
            if (
                $payload &&
                $payload->exp > time() &&
                hash_equals($this->sign($data[0] . '.' . $data[1]), $this->base64Decode($data[2]))
            ) {
                return $payload;
            }
        }
        return null;
    }

    /**
     * 通过base64_encode()生成可在url中安全传输的编码
     */
    private function base64Encode($data): string
    {
        return str_replace('=', '', strtr(base64_encode($data), '+/', '-_'));
    }

    /**
     * 通过base64_decode()解码经过url安全处理的编码
     */
    private function base64Decode($data): string
    {
        if ($remainder = strlen($data) % 4) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * 签名
     */
    private function sign($data): string
    {
        return hash_hmac('sha256', $data, $this->key, true);
    }
}
