<?php

namespace AloneFrame\base;

class Back {
    //code
    protected string|int|null $code = 200;
    //返回提示
    protected string|int|null $msg = null;
    //别名信息
    protected array $alias = [];
    //语言标签msg内容
    protected array $tag = [];
    //语言标签msg符号
    protected string $symbol = '%';
    //语言包
    protected array|string $language = [];
    //当前语言
    protected string|int|null $lang = null;
    //回退语言
    protected string|int|null $fallback = null;
    //语言信息
    protected static array $info = [];
    //返回数据
    protected mixed $data = [];
    //数字类型是否强制转换成数字
    protected bool $digit = true;
    //追加显示
    protected array $merge = [];
    //唯一标识
    protected string|int $make = 'LangHelper';
    //返回状态
    protected int $status = 200;

    /**
     * @param string|int|null $code
     * @param string|int|null $alias
     * @return static
     */
    public static function codes(string|int|null $code = null, string|int|null $alias = null): static {
        return (new static())->code($code, $alias);
    }

    /**
     * @param array|string    $language array=语言包,string=绝对文件或者目录路径
     * @param string|int|null $lang     显示语言类型,没有多语言可不设置
     */
    public function __construct(array|string $language = [], string|int|null $lang = null) {
        $this->language($language, $lang);
    }

    /**
     * 设置语言包,根据code返回内容
     * @param array|string    $language array=语言包,string=绝对文件或者目录路径
     * @param string|int|null $lang     显示语言类型,没有多语言可不设置
     * @return $this
     */
    public function language(array|string $language = [], string|int|null $lang = null): static {
        $this->language = $language;
        $this->lang = $lang ?? $this->lang;
        return $this;
    }

    /**
     * 设置语言
     * @param string|int|null $lang     显示语言类型,没有多语言可不设置
     * @param string|int|null $fallback 回退语言
     * @return $this
     */
    public function lang(string|int|null $lang, string|int|null $fallback): static {
        $this->lang = $lang;
        $this->fallback = $fallback;
        return $this;
    }

    /**
     * 回退语言
     * @param string|int|null $fallback
     * @return $this
     */
    public function fallback(string|int|null $fallback): static {
        $this->fallback = $fallback;
        return $this;
    }

    /**
     * 唯一标识,主要为了保存全局语言
     * @param string|int $make
     * @return $this
     */
    public function make(string|int $make): static {
        $this->make = $make;
        return $this;
    }

    /**
     * 设置code
     * @param string|int|null $code
     * @param string|int|null $alias 设置返回别名
     * @return $this
     */
    public function code(string|int|null $code = null, string|int|null $alias = null): static {
        $this->code = $code;
        $this->alias['code'] = ($alias ?? ($this->alias['code'] ?? 'code')) ?: 'code';
        return $this;
    }

    /**
     * 设置msg的{标签}
     * @param array|string|int $key
     * @param mixed            $val
     * @param string|null      $symbol
     * @return $this
     */
    public function tag(array|string|int $key, mixed $val = null, string|null $symbol = null): static {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->tag[$k] = $v;
            }
            $this->symbol = $val ?? '%';
            return $this;
        }
        $this->tag[$key] = $val;
        $this->symbol = $symbol ?? '%';
        return $this;
    }

    /**
     * 设置语言参数符号
     * @param string $symbol
     * @return $this
     */
    public function symbol(string $symbol): static {
        $this->symbol = $symbol;
        return $this;
    }

    /**
     * 设置返回提示信息,设置了code就不转换了,可使用@开头设置语言key
     * @param string|int|null $msg 如要设置别名,使用code转换,msg可设置null
     * @param array|null      $tag
     * @param string|int|null $alias
     * @return $this
     */
    public function msg(string|int|null $msg = null, array|null $tag = [], string|int|null $alias = null): static {
        $this->msg = $msg;
        $this->alias['msg'] = ($alias ?? ($this->alias['msg'] ?? 'msg')) ?: 'msg';
        return $this->tag($tag);
    }

    /**
     * 设置返回数据包
     * @param mixed           $data
     * @param string|int|null $alias
     * @return $this
     */
    public function data(mixed $data, string|int|null $alias = null): static {
        $this->data = $data;
        $this->alias['data'] = ($alias ?? ($this->alias['data'] ?? 'data')) ?: 'data';
        return $this;
    }

    /**
     * 数字类型是否强制转换成数字
     * @param bool $digit
     * @return $this
     */
    public function digit(bool $digit = true): static {
        $this->digit = $digit;
        return $this;
    }

    /**
     * 追加内容
     * @param string|int|array $key
     * @param mixed            $val
     * @return $this
     */
    public function merge(string|int|array $key, mixed $val = ''): static {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->merge[$k] = $v;
            }
            return $this;
        }
        $this->merge[$key] = $val;
        return $this;
    }

    /**
     * 设置状态
     * @param int $status
     * @return $this
     */
    public function status(int $status): static {
        $this->status = $status;
        return $this;
    }

    /**
     * 返回array
     * @return array
     */
    public function array(): array {
        return $this->create();
    }

    /**
     * 正常输出
     * @param bool $digit 数字类型是否强制转换成数字
     * @return string
     */
    public function json(bool|null $digit = null): string {
        return Frame::json($this->array(), ($digit ?? $this->digit));
    }

    /**
     * 格式化输出
     * @param bool $digit 数字类型是否强制转换成数字
     * @return string
     */
    public function jsons(bool|null $digit = null): string {
        return Frame::jsons($this->array(), ($digit ?? $this->digit));
    }

    /**
     * 获取语言
     * @param string|int|null $key
     * @param mixed|null      $default
     * @return string|null
     */
    public function getLang(string|null|int $key, mixed $default = null): string|null {
        $this->handleLanguage();
        $language = static::$info[$this->make] ?? [];
        if (empty($language)) {
            return $default;
        }
        if (str_starts_with($key, '@')) {
            $keys = substr($key, 1);
            return Frame::tag(Frame::getArr($language, $keys, $default), $this->tag, $this->symbol);
        }
        return Frame::tag(Frame::getArr($language[$this->lang] ?? [], $key, Frame::getArr($language[$this->fallback] ?? [], $key, $default)), $this->tag, $this->symbol);
    }

    /**
     * @param string|int|null $key
     * @param mixed|null      $default
     * @return mixed
     */
    public function getLanguage(string|null|int $key = null, mixed $default = null): mixed {
        return Frame::getArr(static::$info[$this->make] ?? [], $key, $default);
    }

    /**
     * 获取状态
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * 获取code
     * @return string|int|null
     */
    public function getCode(): string|int|null {
        return $this->code;
    }

    /**
     * 获取msg
     * @return string|int|null
     */
    public function getMsg(): string|int|null {
        return $this->msg;
    }

    /**
     * 获取data信息
     * @return  mixed
     */
    public function getData(): mixed {
        return $this->data;
    }

    /**
     * 获取data信息
     * @return  array
     */
    public function getTag(): array {
        return $this->tag;
    }

    /**
     * 获取唯一标识
     * @return int|string
     */
    public function getMake(): string|int {
        return $this->make;
    }

    /**
     * @return void
     */
    private function handleLanguage(): void {
        if (!empty($this->language) && !isset(static::$info[$this->make])) {
            if (is_array($this->language)) {
                static::$info[$this->make] = $this->language;
            } else {
                if (is_dir($this->language)) {
                    $list = Frame::getDirFile($this->language);
                    foreach ($list as $k => $v) {
                        if (Frame::getFileFormat($v) == 'php') {
                            if (is_file($v)) {
                                $key = Frame::strRep(Frame::delFileFormat($k), '\\', '/');
                                $key = Frame::strRep(trim(trim($key, '/')), '/', '.');
                                static::$info[$this->make][$key] = include $v;
                            }
                        }
                    }
                } elseif (is_file($this->language)) {
                    static::$info[$this->make] = include $this->language;
                }
            }
        }
    }

    /**
     * @return array
     */
    private function create(): array {
        $isMsg = isset($this->alias['msg']);
        if ($isMsg) {
            unset($this->alias['msg']);
        }
        $msgKey = ($this->alias['msg'] ?? 'msg') ?: 'msg';
        if (!isset($this->msg) && isset($this->code)) {
            $msgValue = $this->getLang($this->code);
            if (!empty($msgValue)) {
                $arr[$msgKey] = $msgValue;
            }
        } elseif ($isMsg) {
            $arr[$msgKey] = Frame::tag($this->msg, $this->tag, $this->symbol);
        }
        foreach ($this->alias as $k => $v) {
            if ($k != 'msg' && !empty($v) && isset($this->$k)) {
                $arr[$v] = $this->$k;
            }
        }
        return Frame::arrMerge(($arr ?? []), $this->merge);
    }
}