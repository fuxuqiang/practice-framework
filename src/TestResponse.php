<?php

namespace Fuxuqiang\Framework;

use PHPUnit\Framework\Assert;

class TestResponse extends ObjectAccess
{
    private int $status;

    public function __construct($response, $status)
    {
        $this->data = $response;
        $this->status = $status;
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
        return $this->assertStatus(200);
    }

    /**
     * 断言响应内容中包含指定子集
     */
    public function assertArraySubset($subset): static
    {
        Assert::assertTrue(array_replace_recursive($this->data, $subset) == $this->data);
        return $this;
    }

    /**
     * 打印内容
     */
    public function print()
    {
        fwrite(STDERR, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
    }
}
