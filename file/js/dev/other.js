window.aloneOther = function () {
    return {
        //浏览器信息
        browsers() {
            return browser();
        },
        //浏览器信息
        browser(width) {
            const useragent = navigator.userAgent.toLowerCase(), screen = window.screen.width, mobility = 1024;
            return {
                windows: /windows/.test(useragent),
                mac: /mac/.test(useragent) && !/\(i[^;]+;( U;)? cpu.+mac os x/.test(useragent) && screen > mobility,
                linux: /linux/.test(useragent) && !/android|adr/.test(useragent),
                ios: (/iphone|ipod|ipad|ios/.test(useragent) || (/mac/.test(useragent) && screen <= mobility)),
                android: /android|adr/.test(useragent),
                mobile: /mobile|symbianos|windows phone|iphone|ipod|ipad|ios|android|adr|qq|micromessenger/.test(useragent) || screen <= mobility,
                iphone: /iphone/.test(useragent),
                ipad: (/iphone|ipod|ipad/.test(useragent) || (/mac/.test(useragent) && !/iphone/.test(useragent) && screen <= mobility)),
                weixin: /micromessenger/.test(useragent),
                qq: /qq/.test(useragent),
                weibo: /weibo/.test(useragent),
                safari: useragent.indexOf('version') > 0 && /mac os x/.test(useragent),
                ie: /trident|msie|edg/.test(useragent) || window.ActiveXObject || "ActiveXObject" in window,
                chrome: /chrome/.test(useragent),
                firefox: useragent.indexOf('gecko') > -1 && useragent.indexOf('khtml') < 0,
                presto: /presto/.test(useragent),
                mini: (document.documentElement.clientWidth <= (width || 768)),
                lang: (navigator.browserLanguage || navigator.language).toLowerCase()
            }
        },
        play(set, play_rand) {
            var self = {}, i_play = 0, is_play = false,
                play_start = (set.start || function () {
                    console.log('start');
                }), play_stop = (set.stop || function () {
                    console.log('stop');
                }), play_audio = function () {
                    return $('#web_play_' + play_rand).get(0);
                }, play_html = function () {
                    $("body").append('<div style="display: none;"><audio id="web_play_' + play_rand + '" muted="muted">' + ((set.wav || '') ? '' : '<source src="' + (set.file) + '.wav" type="audio/wav">') + ((set.ogg || '') ? '' : '<source src="' + (set.file) + '.ogg" type="audio/ogg">') + ((set.mp3 || '') ? '' : '<source src="' + (set.file) + '.mp3" type="audio/mpeg"><embed height="50" width="50" src="' + (set.file) + '.mp3">') + '</div>');
                    return self;
                };
            $(document).mousedown(function () {
                if (!is_play) {
                    play_audio().muted = true;
                    play_audio().play();
                    play_audio().currentTime = 0;
                    play_audio().pause();
                    is_play = true;
                }
            });
            self.start = function (hits, time) {
                play_start();
                play_audio().muted = false;
                play_audio().play();
                self.playId = setInterval(function () {
                    ++i_play;
                    if (i_play < hits) {
                        play_audio().play();
                    } else {
                        self.stop();
                    }
                }, ((time || 8) * 1000));
                return self;
            };
            self.stop = function () {
                i_play = 0;
                play_stop();
                play_audio().currentTime = 0;
                play_audio().pause();
                clearInterval(self.playId);
                return self;
            }
            return play_html();
        },
        //\n替换成br
        to_br(str) {
            return str.replace(/\n/g, "<br>");
        },
        //br替换成\n
        to_n(str) {
            return str.replace(/<br>/g, "\n");
        },
        //复制
        copy(id, callback) {
            let value, create = document.createElement('textarea');
            if ((typeof (id) === 'function')) {
                value = id();
            } else {
                const obj = document.getElementById(id), type = obj.getAttribute("copy-type") || 'value';
                value = (type == 'value') ? obj.value : (type == 'text' ? obj.innerText : (type == 'html' ? obj.innerHTML : obj.textContent));
            }
            create.value = value;
            create.style.left = '-9999px';
            create.style.position = 'absolute';
            document.getElementsByTagName('body')[0].appendChild(create);
            create.select();
            if (typeof (callback) === 'function') {
                if (callback(value, document.execCommand('copy'))) {
                    if ((typeof (id) !== 'function')) {
                        document.getElementById(id).select();
                    }
                }
            }
            create.remove();
        },
        //倒计时执行
        time_down(end_time, top_time, way, end) {
            if (typeof (top_time) === 'function') {
                const way_ = top_time, end_ = way;
                top_time = end;
                way = way_;
                end = end_;
            }
            let tow = (n) => {
                return n >= 0 && n < 10 ? '0' + n : n;
            }, t = 0, fun = (end_time, top_time, t) => {
                let now_time = top_time ? new Date(top_time.replace(/-/g, "/")) : new Date(),
                    foot_time = new Date(end_time.replace(/-/g, "/")),
                    time = foot_time.getTime() - now_time.getTime() - (t * 1000),
                    d = tow(Math.floor(time / (1000 * 60 * 60 * 24))),
                    h = tow(Math.floor(time / (1000 * 60 * 60) % 24)),
                    m = tow(Math.floor(time / (1000 * 60) % 60)),
                    s = tow(Math.floor(time / 1000 % 60)),
                    str = d + '<span>天</span>' + h + '<span>小时</span>' + m + '<span>分钟</span>' + s + '<span>秒</span>';
                return {d: d, h: h, m: m, s: s, str: str, count: parseInt(d) + parseInt(h) + parseInt(m) + parseInt(s)};
            }, self = {}, clear = setInterval(() => {
                ++t;
                let obj = fun(end_time, top_time, t);
                if (obj.count > 0) {
                    if (typeof (way) === 'function') {
                        way(obj, clear);
                    }
                } else {
                    if (typeof (end) === 'function') {
                        end(obj, clear);
                    }
                    clearInterval(clear);
                }
            }, 1000);
            self.stop = (way) => {
                if (typeof (way) === 'function') {
                    way(clear);
                }
                clearInterval(clear);
            }
            return self
        }
    }
};
window.alone_other = aloneOther();