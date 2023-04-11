<?php

namespace Fuxuqiang\Framework;

use PHPUnit\Framework\Assert;

class TestResponse extends ObjectAccess
{
    public function __construct($response, private readonly ResponseCode $status)
    {
        $this->data = $response;
    }

    /**
     * 动态调用断言方法
     */
    public function __call($name, $args)
    {
        Assert::$name($args[0], $this->data);
        return $this;
    }

    /**
     * 断言响应状态码
     */
    public function assertStatus($status): static
    {
        Assert::assertEquals($status, $this->status);
        return $this;
    }

    /**
     * 断言响应码为200
     */
    public function assertOk(): static
    {
        return $this->assertStatus(ResponseCode::OK);
    }

    /**
     * 打印内容
     */
    public function print(): void
    {
        fwrite(STDERR, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
    }
}
