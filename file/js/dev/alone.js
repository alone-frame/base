window.aloneFrame = function () {
    const alone = {
        //判断变量 false不存在
        isset(data) {
            if (this.is_array(data)) {
                return data.length > 0;
            } else if (this.is_object(data)) {
                return Object.keys(data).length > 0;
            } else if (!data || data === false || data === 'null' || data === 'undefined' || data === null || data === undefined) {
                return false;
            }
            return true;
        },
        //是否对像
        is_object(data) {
            return (data !== null && typeof data === 'object' && !this.is_array(data));
        },
        //是否数组
        is_array(data) {
            return Array.isArray(data);
        },
        //判断是否bool
        is_bool(data) {
            return typeof data === 'boolean';
        },
        //是否闭包
        is_callback(data) {
            return typeof data === 'function';
        },
        //是否字符串
        is_string(data) {
            return typeof data === 'string';
        },
        //判断是否数字
        is_number(data) {
            return typeof data === 'number';
        },
        //支持 json json_string  FormData array key=value&key2=value2 转换成对像
        to_object(data) {
            if (this.is_string(data)) {
                try {
                    return JSON.parse(data);
                } catch (e) {
                    return Object.fromEntries(new URLSearchParams(data));
                }
            } else if (data instanceof FormData) {
                return Object.fromEntries(data.entries());
            } else if (Array.isArray(data)) {
                return Object.fromEntries(data.map((item, index) => [index, item]));
            }
            return data;
        },
        //json json_string  FormData array 转换成aa=11&bb=22
        to_param(obj) {
            return new URLSearchParams(this.to_object((obj || {}))).toString();
        },
        //通过a.b.c.d对像或者数组
        get_arr(obj, key = null, def = null, symbol = '.') {
            obj = obj || {};
            if (this.isset(key)) {
                const arr = key.split((symbol || '.'));
                for (const v of arr) {
                    if (!obj.hasOwnProperty(v)) {
                        return def;
                    }
                    obj = (obj[v] || '') ? obj[v] : def;
                }
            }
            return obj || '';
        },
        //通过a.b.c.d生成对像
        set_arr(key, value, symbol = '.', obj = {}) {
            const arr = key.split((symbol || '.'));
            arr.reduce((acc, cur, index) => {
                if (index === arr.length - 1) {
                    acc[cur] = value;
                } else {
                    acc[cur] = acc[cur] || {};
                }
                return acc[cur];
            }, obj);
            return obj || {};
        },
        //通过a.b.c.d判断key是否存在
        is_key(obj, key, symbol = '.') {
            obj = obj || {};
            if (this.isset(key)) {
                const arr = key.split(symbol);
                for (const v of arr) {
                    if (!obj.hasOwnProperty(v)) {
                        return false;
                    }
                    obj = obj[v];
                }
                return true;
            }
            return false;
        },
        //通过a.b.c.d删除key
        del_key(obj, key, symbol = '.') {
            let object = obj || {};
            if (this.isset(key)) {
                const arr = key.split(symbol);
                const lastKey = arr.pop();
                const target = arr.reduce((acc, cur) => {
                    if (acc && typeof acc === 'object' && acc.hasOwnProperty(cur)) {
                        return acc[cur];
                    }
                    return null;
                }, object);
                if (target && target.hasOwnProperty(lastKey)) {
                    delete target[lastKey];
                    return target;
                } else {
                    return object;
                }
            }
            return object;
        },
        //通过a.b.c.d设置合并
        set_array(arr, key, value, symbol = '.') {
            return this.array_merge(arr, this.set_arr(key, value, symbol));
        },
        //合并对像，数组转对像
        array_merge(array, arr) {
            if (this.isset(array) && this.isset(arr) && this.is_object(array) && this.is_object(arr)) {
                const result = {...array};
                for (const key in arr) {
                    if (arr.hasOwnProperty(key)) {
                        if (typeof arr[key] === 'object' && arr[key] !== null) {
                            if (typeof result[key] === 'object' && result[key] !== null) {
                                result[key] = this.array_merge(result[key], arr[key]);
                            } else {
                                result[key] = arr[key];
                            }
                        } else {
                            result[key] = arr[key];
                        }
                    }
                }
                return result;
            } else if (this.isset(array) && this.is_object(array)) {
                return array;
            }
            return this.is_object(arr) ? arr : {};
        },
        //json格式化
        json_format(json) {
            return JSON.stringify(json, null, "\t");
        },
        //等待时间/毫秒,要使用 await
        sleep(ms, cb) {
            return new Promise(resolve => (setTimeout((typeof cb == 'function' ? (() => (resolve(cb()))) : resolve), ms)));
        },
        //生成token
        get_token() {
            return this.rand_token('xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx') + '-' + Date.now().toString(16)
        },
        //随机id
        rand_token(str = 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx') {
            return ("t" + ((str).replace(/[xy]/g, (c) => {
                const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
                return (v.toString(16));
            })));
        },
        //生成指定数量字符串
        rand_count(num = 10, str) {
            return new Array(num + 1).join(str);
        },
        //生成随机数字
        rand_int(int) {
            return Math.floor(Math.random() * (int || 10));
        },
        //生成随机字符串
        rand_str() {
            const randomHex = (Math.random() * 10000000).toString(16).slice(0, 4);
            const timestamp = Date.now(); // 获取当前时间戳（毫秒）
            const randomString = Math.random().toString().slice(2, 7); // 提取 5 个字符
            return randomHex + timestamp + randomString;
        },
        //生成指定数str
        symbol(str, count) {
            const samples = [];
            for (let i = 1; i <= count; i++) {
                samples.push(str);
            }
            return samples;
        },
        //获取10位时间
        get_time() {
            return Math.floor(Date.now() / 1000);
        },
        //获取当前时间
        get_date(time = 0, format = 'Y-m-d H:i:s') {
            const date = (time || 0) > 0 ? new Date(time * 1000) : new Date();
            const replacements = {
                'Y': date.getFullYear(), // 年份
                'm': String(date.getMonth() + 1).padStart(2, '0'), // 月份
                'd': String(date.getDate()).padStart(2, '0'), // 日期
                'H': String(date.getHours()).padStart(2, '0'), // 小时
                'i': String(date.getMinutes()).padStart(2, '0'), // 分钟
                's': String(date.getSeconds()).padStart(2, '0'), // 秒
            };
            return format.replace(/[YmdHis]/g, match => replacements[match]);
        },
        //隐藏手机号
        hide_mobile(str) {
            return str.toString().replace(/^(\d{3})(\d{4})(\d{4})$/, "$1****$3");
        },
        //输出金额类型
        money(number, decimals = 2, thousands = '', separator = '.') {
            number = parseFloat((number || '0.00')).toFixed((decimals || 2));
            separator = separator ? separator : '.';
            thousands = thousands ? thousands : '';
            const source = String(number).split(".");
            source[0] = source[0].replace(new RegExp('(\\d)(?=(\\d{3})+$)', 'ig'), "$1" + thousands);
            return source.join(separator);
        },
        //通过obj对像替换string内容
        view(string, obj, top = '{', end = '}', symbol = '.', type = false) {
            return string.replace(new RegExp((top || "%") + "([a-zA-Z0-9" + symbol + "]+)" + (end || "%"), "g"), (text, key) => {
                let def = (this.isset(type) ? text : '');
                return this.get_arr(obj, key, def) || def;
            });
        },
        //打开新窗口
        open_web(url) {
            window.open("about:blank").location.href = url
        },
        //关闭窗口
        close_web() {
            if (navigator.userAgent.indexOf("Firefox") !== -1 || navigator.userAgent.indexOf("Chrome") !== -1) {
                window.location.href = "about:blank";
                window.close();
            } else {
                window.opener = null;
                window.open("", "_self");
                window.close();
            }
        },
        //打开中间窗口
        open_window(url, name, width, height) {
            (window.open(url, name, 'resizable=yes, menubar=no,scrollbars=yes,location=no,status=yes,width=' + width + ',height=' + height + ',top=' + ((window.screen.availHeight - 30 - height) / 2) + ',left=' + ((window.screen.availWidth - 10 - width) / 2))).focus();
        },
        //对像转数组
        to_array(data) {
            return Object.keys(data).map((v) => data[v])
        },
        //删除左右侧
        trim(str, char) {
            return this.ltrim(this.rtrim(str, char), char)
        },
        //删除左侧
        ltrim(str, char) {
            if (this.isset(char)) {
                if (str.charAt(0) == char) {
                    str = str.substring(1, str.length);
                }
            } else {
                str = str.replace(/^\s+|\s+$/g, "");
            }
            return str;
        },
        //删除右侧
        rtrim(str, char) {
            if (this.isset(char)) {
                if (str.charAt(str.length - 1) == char) {
                    str = str.substring(0, str.length - 1);
                }
            } else {
                str = str.replace(/^\s+|\s+$/g, "");
            }
            return str;
        },
        //窗口变化
        resize(callable) {
            window.addEventListener("resize", async () => await callable());
        },
        //监听 哈希 事件
        hashchange(callable) {
            window.addEventListener('hashchange', async () => await callable())
        },
        //对像转换string def转换失败时返回,默认原样返回
        to_str(json, def) {
            def = this.isset(def) ? def : json;
            try {
                return typeof json === "object" ? JSON.stringify(json) : def;
            } catch (e) {
                return def;
            }
        },
        //string转换json对像 def 转换失败时返回,默认原样返回
        to_obj(string, def) {
            def = this.isset(def) ? def : string;
            try {
                return typeof string === "string" ? JSON.parse(string) : def;
            } catch (e) {
                return def;
            }
        },
        //获取url参数
        get_param(key, def = '', url = '') {
            const query = (url || window.location.search.substring(1));
            const params = {};
            query.split('&').forEach(param => {
                const [k, v] = param.split('=');
                if (k) {
                    const decodedKey = decodeURIComponent(k);
                    params[decodedKey] = v ? decodeURIComponent(v) : '';
                }
            });
            return this.get_arr(params, key, def);
        },
        //修改url参数
        edit_param(url, param) {
            const https = 'https';
            const http = new RegExp(`^${https}:\\/\\/`, 'i').test(url);
            const uri = new URL((http ? url : (https + '://r/' + url)));
            Object.entries(this.to_object(param)).forEach(([key, val]) => (uri.searchParams.set(key, val)));
            return http ? (uri.origin + uri.pathname + uri.search + uri.hash) : ((uri.pathname.startsWith('/') ? uri.pathname.slice(1) : uri.pathname) + uri.search + uri.hash);
        },
        //当前域名
        domain: {
            //获取当前链接
            get() {
                return window.location.href
            },
            //获取协议
            protocol() {
                return window.location.protocol.split(':').slice(0, 1).join('')
            },
            //获取域名,带http
            url() {
                return this.protocol() + "://" + this.domain();
            },
            //获取一级域名
            one() {
                return document.domain.split('.').slice(-2).join('.')
            },
            //获取当前域名,不带http
            domain() {
                return (window.location.href.split('/').slice(2, 3).join(''))
            }
        },
        //来路域名
        origin: {
            //获取来路链接
            get() {
                return document.referrer
            },
            //获取来路协议
            protocol() {
                return document.referrer.split('://').slice(0, 1).join('')
            },
            //获取来路域名,不带http
            domain() {
                return (document.referrer.split('/').slice(2, 3).join(''))
            },
            //获取来路一级域名
            one() {
                return (document.referrer.split('/').slice(2, 3).join('')).split('.').slice(-2).join('.')
            },
            //获取域名,带http
            url() {
                return this.protocol() + "://" + this.domain();
            }
        },
        //设置head内容
        set_head(label, attr = {}) {
            const elem = document.createElement(label);
            Object.keys(attr).forEach(k => elem.setAttribute(k, attr[k]));
            document.getElementsByTagName("head")[0].appendChild(elem);
            return elem;
        },
        //设置标题
        set_title(title = '') {
            let elem = document.getElementsByTagName('title')[0];
            if (!elem) {
                elem = document.createElement("title");
                document.getElementsByTagName('head')[0].appendChild(elem);
            }
            document.title = title;
            return elem;
        },
        //设置favicon.ico
        set_favicon(ico, attr = {}) {
            const set_attr = function (elem, ico) {
                if (ico) elem.href = ico;
                elem.rel = 'icon';
                elem.setAttribute('icon', 'favicon');
                Object.keys(attr).forEach(k => elem.setAttribute(k, attr[k]));
                return elem;
            }
            let elem = document.querySelector('link[icon="favicon"]');
            if (!elem) {
                elem = document.createElement('link');
                document.getElementsByTagName("head")[0].appendChild(elem);
            }
            return set_attr(elem, ico);
        },
        //设置css文件
        set_link(file, awaits = false) {
            const files = file.split('?')[0];
            const list = document.querySelectorAll('link');
            for (let i = 0; i < list.length; i++) {
                if ((list[i].getAttribute('href')).split('?')[0] == files) {
                    return list[i];
                }
            }
            const elem = document.createElement('link');
            elem.setAttribute('href', file);
            elem.setAttribute('type', 'text/css');
            elem.setAttribute('rel', 'stylesheet');
            document.getElementsByTagName("head")[0].appendChild(elem);
            if (awaits === true) {
                return new Promise((resolve, reject) => {
                    elem.onload = resolve;
                    elem.onerror = reject;
                    return elem;
                });
            }
            return elem;
        },
        //设置js文件
        set_script(file, callback) {
            const files = file.split('?')[0];
            const list = document.querySelectorAll('script[load]');
            for (let i = 0; i < list.length; i++) {
                if ((list[i].getAttribute('src')).split('?')[0] == files) {
                    callback && callback(list[i]);
                    return list[i];
                }
            }
            const elem = document.createElement('script');
            elem.setAttribute('type', 'text/javascript');
            elem.setAttribute('async', '');
            elem.setAttribute('src', file);
            document.getElementsByTagName('head')[0].appendChild(elem);
            if (this.is_callback(callback)) {
                if (elem.readyState) {
                    elem.onreadystatechange = function () {
                        if (elem.readyState == 'compvare' || elem.readyState == 'loaded') {
                            elem.onreadystatechange = null;
                            callback();
                        }
                    };
                } else {
                    elem.onload = () => (callback());
                }
                return elem;
            }
            return new Promise((resolve, reject) => {
                elem.onload = resolve;
                elem.onerror = reject;
                return elem;
            });
        },
        //await lay_use
        lay_use(model, callback = null) {
            model = (this.is_array(model) || this.is_string(model)) ? model : ((this.is_array(callback) || this.is_string(callback)) ? callback : []);
            callback = this.is_callback(model) ? model : (this.is_callback(callback) ? callback : (exports) => exports);
            return new Promise((resolve) => layui.use(model, (exports) => resolve((callback && callback(exports)))));
        }
    };
    alone.cookie = {
        //time为有效时间/秒
        set(key, val, time = 0, {path = '/', domain = null} = {}) {
            const value = alone.to_str(val);
            let cookie = `${key}=${encodeURIComponent(alone.to_str(val))}`;
            if (time > 0) {
                const expires = new Date(Date.now() + time * 1000).toUTCString();
                cookie += `; expires=${expires}`;
            }
            document.cookie = `${cookie}; path=${path}; domain=${domain || document.domain};`;
            return value;
        },
        get(key = null, def = null) {
            return alone.get_arr(this.list(), key, def);
        },
        del(key, {path = '/', domain = null} = {}) {
            const remove = function (k) {
                document.cookie = `${k}=; path=${path}; domain=${domain || document.domain}; expires=Thu, 01 Jan 1970 00:00:00 UTC;`;
            }
            if (alone.isset(key)) {
                if (alone.is_array(key)) {
                    key.forEach((k) => remove(k));
                } else {
                    remove(key);
                }
            }
        },
        delete() {
            this.del(Object.keys(this.list()));
        },
        list() {
            const list = (document.cookie || '').split("; ");
            return alone.isset(list) ? (list.reduce((acc, curr) => {
                const [key, val] = curr.split("=");
                const value = alone.to_obj(decodeURIComponent(val));
                if (key && alone.isset(value)) {
                    acc[key] = value;
                }
                return acc;
            }, {})) : {};
        }
    };
    alone.session = {
        set(key, val) {
            const value = alone.to_str(val);
            window.sessionStorage.setItem(key, value);
            return value;
        },
        get(key = null, def = null) {
            return alone.get_arr(this.list(), key, def);
        },
        del(key) {
            if (alone.isset(key)) {
                if (alone.is_array(key)) {
                    key.forEach((k) => window.sessionStorage.removeItem(k));
                } else {
                    window.sessionStorage.removeItem(key);
                }
            }
        },
        delete() {
            window.sessionStorage.clear();
        },
        list() {
            const arr = {};
            const obj = window.sessionStorage;
            Object.keys(obj).forEach((key) => {
                const val = alone.to_obj(obj[key]);
                if (key && alone.isset(val)) {
                    arr[key] = val;
                }
            });
            return arr;
        }
    };
    alone.storage = {
        set(key, val, time = 0) {
            window.localStorage.setItem(key, alone.to_str({
                data: val,
                time: ((time || 0) > 0 ? (alone.get_time() + time) : 0)
            }));
            return val;
        },
        get(key = null, def = null) {
            return alone.get_arr(this.list(), key, def);
        },
        del(key) {
            if (alone.isset(key)) {
                if (alone.is_array(key)) {
                    key.forEach((k) => window.localStorage.removeItem(k));
                } else {
                    window.localStorage.removeItem(key);
                }
            }
        },
        delete() {
            window.localStorage.clear();
        },
        list() {
            const arr = {};
            const obj = window.localStorage;
            Object.keys(obj).forEach((key) => {
                const val = alone.to_obj(obj[key]) || {};
                if (val.time > 0 && alone.get_time() > val.time) {
                    this.del(key);
                } else if (key && alone.isset(val.data)) {
                    arr[key] = val.data;
                }
            });
            return arr;
        }
    };
    alone.global = function (name = null, symbol = ".") {
        return {
            symbol: alone.isset(symbol) ? symbol : ".",
            name: "global_" + (alone.isset(name) ? name : "_app_object") + "_cache",
            //设置
            set(key, val = null) {
                const value = alone.is_object(key) ? key : alone.set_arr(key, val, this.symbol);
                this.object(value);
                return value;
            },
            //获取
            get(key = null, def = null) {
                return alone.get_arr(this.list(), key, def, this.symbol) || def;
            },
            //删除,支持传入array删除多个,返回删除数量
            del(key) {
                if (alone.isset(key)) {
                    if (alone.is_array(key)) {
                        key.forEach((k) => this.save(alone.del_key(this.list(), k, this.symbol)));
                    } else {
                        this.save(alone.del_key(this.list(), key, this.symbol));
                    }
                }
            },
            //清空
            delete() {
                window[this.name] = {};
            },
            //获取全部
            list() {
                return window[this.name] || {};
            },
            //传入对像合并,true=优选
            object(object = {}, merge = false) {
                return this.save((merge ? alone.array_merge(object, this.list()) : alone.array_merge(this.list(), object)));
            },
            //保存
            save(object) {
                window[this.name] = object;
                return object;
            }
        };
    };
    alone.globals = alone.global('alone_frame');
    return alone;
};
window.alone_frame = aloneFrame();