<?php

namespace AloneFrame\base;

use think\Validate;
use think\exception\ValidateException;
use think\Facade\Validate as ValidateFacade;

/**
 * 数据验证 官方文档
 * https://doc.thinkphp.cn/@think-validate/rule_buildin.html
 * https://www.workerman.net/doc/webman/components/validation.html
 */
class ThinkValidate extends ValidateFacade{
    /**
     * think验证数据
     * @param array         $data  要验证的数据
     * @param array         $rules 验证规则
     * @param Validate|null $validate
     * @return array
     */
    public static function alone(array $data, array $rules, Validate|null $validate = null): array {
        //支持以下写法,key和官方一样
        //https://doc.thinkphp.cn/@think-validate/rule_buildin.html
        /*
            $rules = [
                'name' => '不能为空',//这种不支持array
                'pass' => function ($val) {
                    return $val == '123456' ? true : '密码错误';
                },
                'email' => [
                    'require' => '邮箱不能为空',
                    'email' => '邮箱格式错误',
                    'max:25' => ['code' => 401, 'msg' => '名称最多不能超过25个字符'],
                    'length:3,25' => '402|只能在3-25之间',
                    'regex:/^[a-zA-Z]+$/' => function () {
                        return '只能包含英文字符';
                    }
                ]
            ];
         */
        $self = !empty($validate) ? $validate : new Validate();
        foreach ($rules as $name => $arr) {
            if (is_array($arr)) {
                $rule = '';
                $msg = [];
                foreach ($arr as $k => $v) {
                    $rule .= "|$k";
                    $key = explode(":", $k);
                    $val = (is_callable($v) ? $v() : $v);
                    $msg[$key[0]] = is_array($val) ? $val : (string) $val;
                }
                $self->rule($name, trim($rule, '|'), $msg);
            } elseif (is_callable($arr)) {
                $self->rule($name, $arr);
            } else {
                $self->rule($name, 'require', ['require' => (string) $arr]);
            }
        }
        try {
            $self->failException(true)->check($data);
        } catch (ValidateException $e) {
            return ['key' => $e->getKey(), 'msg' => $e->getError()];
        }
        return [];
    }
}