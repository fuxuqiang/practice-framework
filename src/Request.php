<?php

namespace Fuxuqiang\Framework;

use Exception;

class Request extends Arr
{
    private $uri, $server, $user, $exists, $perPage, $rules;

    /**
     * 初始化请求参数和验证规则
     */
    public function __construct(array $server, array $data, callable $exists, $perPage)
    {
        $this->server = $server;

        if (!$this->data = $data) {
            if (isset($server['CONTENT_TYPE']) && $server['CONTENT_TYPE'] == 'application/json') {
                $this->data = json_decode(file_get_contents('php://input'), true);
            } else {
                parse_str(file_get_contents('php://input'), $this->data);
            }
        }

        $this->exists = $exists;

        $this->perPage = $perPage;

        $this->uri = isset($server['REQUEST_URI']) ? ltrim($server['REQUEST_URI'], '/') : '';

        $this->rules = [
            'mobile' => function ($mobile) {
                    return preg_match('/^1[2-9]\d{9}$/', $mobile);
                },
            'exists' => $this->exists,
            'array' => 'is_array',
            'min' => function ($val, $min) {
                    return $val >= $min;
                },
            'int' => function ($val) {
                    return filter_var($val, FILTER_VALIDATE_INT) !== false;
                },
            'nq' => function ($val, $diff) {
                    return $val != $diff;
                },
            'unique' => function (...$args) {
                    return !call_user_func($this->exists, ...$args);
                },
            'str' => function ($val) {
                    return is_string($val);
                },
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
     * 获取请求的uri
     */
    public function uri()
    {
        return $this->uri;
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
