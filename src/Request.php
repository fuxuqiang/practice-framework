<?php

namespace Fuxuqiang\Framework;

use Exception;

class Request extends Arr
{
    private $url, $user, $exists, $rules;

    /**
     * 初始化请求参数和验证规则
     */
    public function __construct(
        private array $server,
        array $data,
        callable $exists,
        private int $perPage
    ) {
        if (!$this->data = $data) {
            if (isset($server['CONTENT_TYPE']) && $server['CONTENT_TYPE'] == 'application/json') {
                $this->data = json_decode(file_get_contents('php://input'), true);
            } else {
                parse_str(file_get_contents('php://input'), $this->data);
            }
        }

        $this->exists = $exists;

        $this->url = isset($server['REQUEST_URI']) ? ltrim(strstr($server['REQUEST_URI'], '?', true), '/') : '';

        $this->rules = [
            'mobile' => fn($mobile) => preg_match('/^1[2-9]\d{9}$/', $mobile),
            'exists' => $this->exists,
            'array' => 'is_array',
            'min' => fn($val, $min) => $val >= $min,
            'int' => fn($val) => filter_var($val, FILTER_VALIDATE_INT) !== false,
            'nq' => fn($val, $diff) => $val != $diff,
            'unique' => fn(...$args) => !call_user_func($this->exists, ...$args),
            'str' => fn($val) => is_string($val),
        ];
    }

    /**
     * 获取$server
     */
    public function server()
    {
        return $this->server;
    }

    /**
     * 获取请求的url
     */
    public function url()
    {
        return $this->url;
    }

    /**
     * 获取token
     */
    public function token()
    {
        if (
            isset($this->server['HTTP_AUTHORIZATION'])
            && strpos($this->server['HTTP_AUTHORIZATION'], 'Bearer ') === 0
        ) {
            return substr($this->server['HTTP_AUTHORIZATION'], 7);
        }
    }

    /**
     * 设置请求用户
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * 获取请求用户
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * 获取请求用户id
     */
    public function userId()
    {
        return $this->user->id;
    }

    /**
     * 获取分页参数
     */
    public function pageParams()
    {
        return [$this->data['page'] ?? 1, $this->data['per_page'] ?? $this->perPage];
    }

    /**
     * 验证请求参数
     */
    public function validate(array $paramsRules)
    {
        foreach ($paramsRules as $param => $ruleItems) {
            if (strpos($param, '.*.')) {
                $keys = explode('.*.', $param);
                if (!is_array($this->data[$keys[0]]) || empty($this->data[$keys[0]])) {
                    throw new Exception('无效的' . $keys[0]);
                }
                foreach ($this->data[$keys[0]] as $key => $item) {
                    $this->validateItem($item[$keys[1]] ?? null, $ruleItems, str_replace('*', $key, $param));
                }
            } else {
                $this->validateItem($this->data[$param] ?? null, $ruleItems, $param);   
            }
        }

        return $this->get(...array_keys($paramsRules));
    }

    /**
     * 根据规则验证参数
     */
    public function validateItem($data, $ruleItems, $param)
    {
        $ruleItems = explode('|', $ruleItems);
        if (in_array('required', $ruleItems) && empty($data)) {
            throw new Exception('缺少参数' . $param);
        }
        foreach ($ruleItems as $ruleItem) {
            $ruleItem = explode(':', $ruleItem);
            if (!isset($this->rules[$ruleItem[0]])) {
                continue;
            }
            if (
                isset($data)
                && !$this->rules[$ruleItem[0]](
                    $data,
                    ...(isset($ruleItem[1]) ? explode(',', $ruleItem[1]) : [])
                )
            ) {
                throw new Exception('无效的' . $param);
            }      
        }
    }
}
