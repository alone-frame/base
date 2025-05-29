window.alone_http = function (uri = '', option = {}) {
    const list = {
        post: async (p = '', a = '') => true,
        put: async (p = '', a = '') => true,
        patch: async (p = '', a = '') => true,
        delete: async (p = '', a = '') => true,
        get: async (p = '', a = '') => false,
        head: async (p = '', a = '') => false,
        options: async (p = '', a = '') => false,
        link: async (p = '', a = '') => true,
        unlink: async (p = '', a = '') => true,
        trace: async (p = '', a = '') => false,
        connect: async (p = '', a = '') => false
    };
    const deploy = {
        //实际请求完整的url
        url: "",
        //请求路径
        path: "",
        //请求域名
        domain: (typeof uri === 'string' ? uri : ""),
        //超时时间
        timeout: 10,
        //请求方法,不分大小写
        method: "",
        //失败重试次数,重试等待时间/毫秒
        retry: 0,
        //重试等待时间/ms
        wait: 500,
        //请求头
        header: {},
        //URL请求参数
        query: "",
        //发送的请求信息
        data: null,
        //请求数据类型
        mold: "",
        //是否开启调试
        log: false,
        //认帐的帐号
        user: "",
        //认证密码
        pass: "",
        //设置 响应类型
        type: "",
        //覆盖响应的 MIME 类型
        mime: "",
        //跨域请求时是否发送凭据
        cross: false,
        //true开启缓存,false=禁用缓存
        cache: false,
        //是否开启ajax
        ajax: false,
        //有些请求没有提交数据,是否强制提交
        force: false,
        //当前设置是否允许提交数据
        refer: false,
        //当前请求类型
        mode: "xhr",
        //请求 拦截器 设置 (option,back)请求内容(data) 返回要设置的参数
        request: {option: null, data: null},
        //响应 拦截器,success(data,status,xhr) && error(data,status,xhr) 返回false不拦截
        response: {success: null, error: null},
        //过程 监听器 可做进度条 (res, status, type, xhr)
        process: null,
        //fetch是否开启流
        style: true,
        //请求配置
        option: null
    };
    const util = {
        set: (k, v) => k.split('.').reduceRight((acc, cur) => ({[cur]: acc}), v),
        get: (obj, k, def) => (k ? (k.split('.').reduce((acc, cur) => acc && acc[cur], obj) || def) : obj),
        is: (data) => (typeof data === 'object' && data !== null),
        merge(target, source) {
            Object.entries(source).forEach(([k, v]) => {
                if (util.is(v)) {
                    if (util.is(target[k])) {
                        util.merge(target[k], v);
                    } else {
                        target[k] = v;
                    }
                } else {
                    target[k] = v;
                }
            });
            return target;
        },
        json(s) {
            try {
                return (typeof s === "string" ? JSON.parse(s) : s);
            } catch (e) {
                return s;
            }
        },
        time: () => (Math.floor(Date.now() / 1000)),
        date(time, format) {
            format = format || "Y-m-d H:i:s";
            const now = (time || 0) > 0 ? new Date(time * 1000) : new Date();
            const replacements = {
                'Y': now.getFullYear(), // 年份
                'm': String(now.getMonth() + 1).padStart(2, '0'), // 月份
                'd': String(now.getDate()).padStart(2, '0'), // 日期
                'H': String(now.getHours()).padStart(2, '0'), // 小时
                'i': String(now.getMinutes()).padStart(2, '0'), // 分钟
                's': String(now.getSeconds()).padStart(2, '0'), // 秒
            };
            return format.replace(/[YmdHis]/g, match => replacements[match]);
        },
        sleep: (ms, cb) => (new Promise(resolve => (setTimeout((typeof cb == 'function' ? (() => (resolve(cb()))) : resolve), ms)))),
        symbol(str, count) {
            const samples = [];
            for (let i = 1; i <= count; i++) {
                samples.push(str);
            }
            return samples;
        },
        param(url, param) {
            const https = 'https';
            const http = new RegExp(`^${https}:\\/\\/`, 'i').test(url);
            const uri = new URL((http ? url : (https + '://r/' + url)));
            Object.entries(util.object(param)).forEach(([key, val]) => (uri.searchParams.set(key, val)));
            return http ? (uri.origin + uri.pathname + uri.search + uri.hash) : ((uri.pathname.startsWith('/') ? uri.pathname.slice(1) : uri.pathname) + uri.search + uri.hash);
        },
        form(d) {
            const form = new FormData();
            ((d) && Object.entries(util.object(d)).forEach(([key, val]) => (form.append(key, val))));
            return form;
        },
        object(data) {
            if (typeof data === 'string') {
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
        }
    };
    const config = (util.is(uri) ? ({...deploy, ...uri}) : ({...deploy, ...option}));
    const self = {...list, conf: {...config}};
    const plugin = {
        //增加axios插件 方法对像 获取配置包, 响应拦截器包, 回调包(内容,状态码,类型,xhr)
        axios(then, get, conf, cb) {
            let option = {};
            ((get('cache') !== true) && (then.header('Cache-Control', 'no-cache'), (then.header('Pragma', 'no-cache'))));
            option.headers = get('header');
            option.timeout = get('timeout');
            (get('type') && (option.responseType = get('type')));
            option.withCredentials = get('cross') === true;
            option.onUploadProgress = (event) => (cb(Math.round(event.loaded * 100 / event.total), 200, 'upload', event));
            option.onDownloadProgress = (event) => (cb(Math.round(event.loaded * 100 / event.total), 200, 'process', event));
            axios.create(conf(option))[get('method').toLowerCase()](get('url'), get('data')).then((res) => {
                cb(res.data, res.status, 'success', res);
            }).catch((err) => {
                let res = err.response;
                cb(res.statusText || 'Network error', res.status || 444, 'error', res);
            });
        },
        //增加ajax插件 方法对像 获取配置包, 响应拦截器包, 回调包(内容,状态码,类型,xhr)
        ajax(then, get, conf, cb) {
            let option = {xhrField: {withCredentials: get('cross') === true}};
            option.data = get('data');
            option.url = get('url');
            option.headers = get('header');
            option.method = get('method');
            option.cache = get('cache') === true;
            option.timeout = get('timeout');
            option.processData = false;
            option.contentType = false;
            option.xhr = () => {
                let xhr = new window.XMLHttpRequest();
                xhr.onprogress = (event) => ((event.lengthComputable) & cb(Math.round(event.loaded * 100 / event.total), 200, 'process', event));
                xhr.upload.onprogress = (event) => ((event.lengthComputable) && cb(Math.round(event.loaded * 100 / event.total), 200, 'upload', event));
                return xhr;
            };
            option.success = (response, status, xhr) => (cb(response, xhr.status, 'success', xhr));
            option.error = (xhr, status, text) => (cb(text || 'Network error', xhr.status || 444, 'error', xhr));
            $.ajax(conf(option, (option) => {
                option.beforeSend = (xhr) => (cb(xhr.responseText, 200, 'beforeSend', xhr));
                option.complete = (xhr) => (cb(xhr.responseText, xhr.status, 'complete', xhr));
                return option;
            }));
        }
    }
    const http = {
        conf: (k) => (util.get(self.conf, k)),
        restore: () => (self.conf = {...config}),
        option: (option, back) => (self.conf.option = (((self.conf.request.option || ((option) => option))(option, (back || ((option) => (option))))) || option), self.conf.option),
        create: () => (Object.keys(plugin).forEach((k) => (self.plugin(k, plugin[k]))), Object.keys(list).forEach((m) => (self[m] = (p = '', a = '') => (self.method(m), http.send(p, a)))), self.mode('xhr')),
        send: (p, a) => (((typeof p == 'function') ? self.res(p, a) : (self.ajax(a), self.path(p))), http.handle()),
        handle() {
            const mold = http.conf('mold'), domain = http.conf('domain'), path = http.conf('path');
            self.conf.url = (domain ? (path ? (domain.replace(/\/$/, '') + "/" + path.replace(/^\/+|\/+$/g, '')) : domain) : path);
            self.conf.method = (http.conf('method') ? http.conf('method') : (http.conf('data') ? "POST" : "GET")).toUpperCase();
            self.conf.timeout = ((http.conf('timeout') || 10) * 1000);
            self.conf.refer = ((list[http.conf('method').toLowerCase()] || (() => false))());
            (http.conf('query')) && (self.conf.url = util.param(self.conf.url, http.conf('query')));
            return http.header(mold).body(mold).call((self.plugins[self.conf.mode] || self.plugins.xhr));
        },
        header(opt, obj = {}) {
            obj.data = "application/x-www-form-urlencoded";
            obj.json = "application/json";
            obj.text = "text/plain";
            obj.xml = "application/xml";
            (obj[opt]) && self.header('Content-Type', obj[opt]);
            (http.conf('ajax') === true) && self.header('X-Requested-With', 'XMLHttpRequest');
            ((http.conf('user')) && self.header('Authorization', ('Basic ' + btoa((http.conf('user') + ':' + (http.conf('pass') || ""))))));
            return http;
        },
        body(opt, obj = {}) {
            obj.data = (d) => (new URLSearchParams(util.object(d)).toString())
            obj.json = (d) => (JSON.stringify(util.object(d)))
            obj.form = (d) => (util.form(d));
            self.conf.data = (self.conf.force === false && self.conf.refer === false) ? null : (obj[opt] || ((d) => (d)))((((self.conf.request.data || ((d) => d))(http.conf('data'), util.object)) || http.conf('data')));
            return http;
        },
        call: async (plugins) => {
            let revert, res, success = self.conf.response.success,
                error = self.conf.response.error,
                process = self.conf.process;
            try {
                res = await http.promise((typeof process === 'function' ? (res, status, type, xhr) => {
                    (process) && process(res, status, type, xhr);
                } : null), plugins);
                if (success) {
                    revert = success(res.data, res.status, res.xhr);
                    if (revert !== undefined) {
                        http.restore();
                        return revert;
                    }
                }
                http.restore();
                return res;
            } catch (e) {
                let come = {data: (e.message || e.data), status: e.status, type: e.type, xhr: e.xhr};
                if (http.conf('retry') > 1) {
                    --self.conf.retry;
                    return await util.sleep(http.conf('wait'), () => (http.call(plugins)));
                }
                if (error) {
                    revert = error(come.data, come.status, come.xhr);
                    if (revert !== undefined) {
                        http.restore();
                        return revert;
                    }
                }
                http.restore();
                return come;
            }
        },
        promise(cb, plugins) {
            return new Promise((resolve, reject) => {
                let error = false, success = false, st = util.time();
                plugins((res, status, type, xhr) => {
                    let data = util.json(res), log = (int) => {
                        if (http.conf('log') === true) {
                            let top = util.symbol('=', 25).join('');
                            let mid = util.symbol('-', 35).join('');
                            let end = util.symbol('*', 22).join('');
                            console.group(top + '' + ' (' + http.conf('method') + ') ' + util.date() + ' (' + http.conf('mold') + ') ' + '\x1B[0m' + top);
                            console.log('url:' + http.conf('url'), 'timeout:' + http.conf('timeout'), 'execute:' + (util.time() - st));
                            http.conf('header') && console.log('headers', http.conf('header'));
                            http.conf('data') && console.log('request', http.conf('data'));
                            console.log('\x1B[35m' + mid + 'response' + mid + '\x1B[0m');
                            console.log(data);
                            console.log('\x1B[' + int + 'm' + end + ' (' + status + ') ' + util.date() + ' (' + self.conf.mode + ') ' + end + '\x1B[6m');
                            console.groupEnd();
                        }
                    };
                    if (cb && typeof cb === "function") cb(data, status, type, xhr);
                    if (status >= 200 && status <= 302) {
                        (type == 'success') && (success = true, log(33), resolve({data, status, type, xhr}));
                    } else if (error === false) {
                        (error = true, log(31), reject({data, status, type, xhr}));
                    }
                });
            });
        }
    };
    self.plugins = {
        xhr(cb) {
            let xhr = new window.XMLHttpRequest();
            xhr.open(http.conf('method'), http.conf('url'), true);
            //一个布尔值，指示是否发送跨域请求时带上凭据（如 cookies）。
            xhr.withCredentials = http.conf('cross') === true;
            //用于覆盖响应的 MIME 类型，适用于需要处理特定类型响应的场景。
            http.conf('mime') && xhr.overrideMimeType(http.conf('mime'));
            //指定响应类型（如 ""、"text"、"json"、"document"、"blob"、"arraybuffer"）。影响 xhr.response 的内容格式。
            http.conf('type') && (xhr.responseType = http.conf('type'));
            //设置请求的超时时间（以毫秒为单位）。如果请求在指定时间内未完成，将触发 ontimeout 事件。
            xhr.timeout = http.conf('timeout');
            //true使用缓存
            ((http.conf('cache') !== true) && (self.header('Cache-Control', 'no-cache'), (self.header('Pragma', 'no-cache'))));
            // 设置头部信息
            Object.keys(self.conf.header).forEach(key => (xhr.setRequestHeader(key, self.conf.header[key])))
            // 监听上传进度
            xhr.upload.onprogress = (event) => ((event.lengthComputable) && cb(Math.round(event.loaded * 100 / event.total), 200, 'upload', event))
            // 监听请求进度
            xhr.onprogress = (event) => ((event.lengthComputable) && cb(Math.round(event.loaded * 100 / event.total), 200, 'process', event));
            // 监听请求结束（无论成功或失败）
            xhr.onloadend = () => ((xhr.status >= 200 && xhr.status < 300) ? cb(xhr.response, xhr.status, 'success', xhr) : cb(xhr.statusText || 'Network error', xhr.status || 444, 'error', xhr));
            // 发送请求
            http.option(xhr, (xhr) => {
                // 监听请求状态
                xhr.onreadystatechange = () => ((xhr.readyState === XMLHttpRequest.DONE) && cb(xhr.response, xhr.status, 'state', xhr));
                // 监听请求成功
                xhr.onload = () => (cb(xhr.response, xhr.status, 'success', xhr));
                // 监听请求错误,主要用于处理网络层面的错误，而不是 HTTP 错误状态。
                xhr.onerror = () => (cb((xhr.statusText || 'Network error'), (xhr.status || 441), 'error', xhr));
                // 监听请求中止
                xhr.onabort = () => (cb((xhr.statusText || 'Request was canceled'), (xhr.status || 442), 'error', xhr));
                // 监听请求超时
                xhr.ontimeout = () => (cb((xhr.statusText || 'Request timed out'), (xhr.status || 443), 'error', xhr));
                return xhr;
            }).send(http.conf('data'));
        },
        fetch(cb) {
            let xhr = {};
            const ctrl = new AbortController();
            xhr.body = http.conf('data');
            // 获取信号
            xhr.signal = ctrl.signal;
            // 设置请求方法 （如 GET, POST, PUT, DELETE 等）。默认是 GET
            xhr.method = http.conf('method');
            //设置请求头
            xhr.headers = http.conf('header');
            // 设置凭证
            xhr.credentials = http.conf('cross') === true ? 'include' : 'same-origin';
            // 设置缓存模式
            xhr.cache = http.conf('cache') !== true ? 'default' : 'reload';
            // 设置重定向行为
            xhr.redirect = 'follow';
            // 设置请求模式
            xhr.mode = 'cors';
            // 设置引用来源
            xhr.referrer = 'unsafe-url';
            // 设置引用来源策略 同上
            xhr.referrerPolicy = 'unsafe-url';
            // 设置请求是否在页面卸载后继续进行
            xhr.keepalive = true;
            //定时器
            const timeout = setTimeout(() => (ctrl.abort()), http.conf('timeout'));
            //关闭定时器
            const clear = () => (clearTimeout(timeout));
            //开始请求
            fetch(http.conf('url'), http.option(xhr)).then(async (res) => {
                try {
                    if (!res.ok) {
                        return cb(res.statusText, res.status, 'error', res);
                    } else if (self.conf.style === false) {
                        return cb(await res.text(), res.status, 'success', res);
                    }
                    const len = res.headers.get('Content-Length');
                    const total = len ? parseInt(len, 10) : 0;
                    let loaded = 0;
                    const reader = res.body.getReader();
                    const type = res.headers.get('Content-Type');
                    const charset = type && type.match(/charset=([^;]+)/) ? type.match(/charset=([^;]+)/)[1] : 'utf-8';
                    const decoder = new TextDecoder(charset);
                    let result = '';
                    while (true) {
                        const {done, value} = await reader.read();
                        if (done) {
                            cb(result, res.status, 'success', res);
                            return;
                        }
                        loaded += value.length;
                        if (total > 0) {
                            cb(Math.round(loaded * 100 / total), 200, 'process', res);
                        }
                        result += decoder.decode(value, {stream: true});
                    }
                } catch (err) {
                    cb(err.statusText || 'Network error', err.status || 444, 'error', err);
                } finally {
                    clear();
                }
            });
        }
    };
    //请求 拦截器 设置(option,back),请求内容(data);返回要设置的参数
    self.req = (x, b) => (self.conf.request.option = x, self.conf.request.data = b, self);
    //响应 拦截器 success(data,status,xhr)成功和error(data,status,xhr)失败 返回undefined不拦截
    self.res = (s, e) => (self.conf.response.success = s, self.conf.response.error = e, self);
    //过程 监听器 可做进度条 (res, status, type, xhr)
    self.process = (d) => (self.conf.process = d, self);
    //设置域名,第二参数请求方法
    self.domain = (d, m) => (self.conf.domain = d, self.conf.method = m || self.conf.method, self);
    //设置请求路径,第二参数请求方法
    self.path = (d, m) => (self.conf.path = d || self.conf.path, self.conf.method = m || self.conf.method, self);
    //发送 GET 请求参数 支持json json_string  FormData array key=value&key2=value2
    self.query = (d, a) => (self.ajax(a), self.conf.query = d, self);
    //HTTP 请求方法，如 "GET"、"POST"、"PUT"、"DELETE" 等。方法名不区分大小写
    self.method = (d) => ((self.conf.method = d || self.conf.method), self);
    //设置头部信息,key支持a.b.c
    self.header = (k, v) => (self.conf.header = util.merge(self.conf.header, ((typeof k === 'object' && k !== null) ? k : util.set(k, v))), self);
    //开启ajax请求
    self.ajax = (i) => (self.conf.ajax = i || self.conf.ajax, self);
    //设置请求的超时时间（以毫秒为单位）。如果请求在指定时间内未完成，将触发 ontimeout 事件。
    self.timeout = (d) => ((self.conf.timeout = d), self);
    //失败重试次数,重试等待时间/毫秒
    self.retry = (h, t) => (((self.conf.retry = h + 1, self.conf.wait = t || self.conf.wait), self));
    //是否输出调试信息
    self.log = (d) => (self.conf.log = d, self);
    //发送 URL编码 数据:支持json json_string  FormData array key=value&key2=value2
    self.data = (d, a) => (self.ajax(a), self.conf.mold = 'data', self.conf.data = d, self);
    //发送 JSON 数据,支持json json_string  FormData array key=value&key2=value2
    self.json = (d, a) => (self.ajax(a), self.conf.mold = 'json', self.conf.data = d, self);
    //发送 FormData 数据,支持json json_string  FormData array key=value&key2=value2
    self.form = (d, a) => (self.ajax(a), self.conf.mold = 'form', self.conf.data = d, self);
    //发送 自定义 数据 不做处理
    self.custom = (d, a) => (self.ajax(a), self.conf.mold = 'custom', self.conf.data = d, self);
    //发送 文本数据 任何文本 不做处理
    self.text = (d, a) => (self.ajax(a), self.conf.mold = 'text', self.conf.data = d, self);
    //发送 XML 数据 ,只支持XML格式的字符串 不做处理
    self.xml = (d, a) => (self.ajax(a), self.conf.mold = 'xml', self.conf.data = d, self);
    //一个布尔值，指示是否发送跨域请求时带上凭据（如 cookies）。
    self.cross = (d) => (self.conf.cross = d, self);
    //true开启缓存,false=禁用缓存
    self.cache = (d) => (self.conf.cache = d, self);
    //指定响应类型（如 ""、"text"、"json"、"document"、"blob"、"arraybuffer"）。影响 xhr.response 的内容格式。
    self.type = (d) => (self.conf.type = d, self);
    //用于覆盖响应的 MIME 类型，适用于需要处理特定类型响应的场景。
    self.mime = (d) => (self.conf.mime = d, self);
    //用于 HTTP 基本认证的用户名和密码。如果不需要身份验证，可以省略此参数
    self.auth = (u, p) => (self.conf.user = u, self.conf.pass = p, self);
    //有些请求没有提交数据,是否强制提交
    self.force = (d) => (self.conf.force = d, self);
    //选择请求模式,xhr,fetch,jq,axios
    self.mode = (n, t) => ((self.conf.style = t !== false), (self.conf.mode = (Object.keys(self.plugins).includes(n) ? n : "xhr")), self);
    //自定加插件,n名字,插件包c(get,conf,cb)  分别是 方法对像 获取配置包, 响应拦截器包, 回调包(内容,状态码,类型,xhr)
    self.plugin = (n, c = null) => (((typeof c === 'function') && (Object.assign(self.plugins, {[n]: (cb) => (c(self, http.conf, http.option, cb))}))), self.mode(n), self);
    //自定方法请求
    self.send = (p, a) => (http.send(p, a));
    //执行并发,[并发对像,success(key,内容,状态码,xhr),error(key,内容,状态码,xhr)]
    self.exec = async (object, success, error) => {
        const list = Object.values(object), keys = Object.keys(object);
        await Promise.allSettled(list).then(res => res.forEach((v, k) => {
            const key = keys[k], status = v.status, val = v.value || {};
            if (status == 'fulfilled') {
                if (val.type == 'success') {
                    ((typeof success == 'function') && success(key, val.data, val.status, val.xhr));
                } else {
                    ((typeof error == 'function') && error(key, val.data, val.status, val.xhr));
                }
            } else if (typeof error == 'function') {
                error(key, v.reason, 444, v);
            }
        }));
        return self;
    }
    return http.create();
};