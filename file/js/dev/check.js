window.aloneCheck = function () {
    return {
        //大小写字母数字或下划线
        user_preg(data, top, end) {
            let preg = new RegExp('^[a-zA-Z0-9_]{' + top + ',' + end + '}$');
            return preg.test(data);
        },
        //大小写字母数字符号的组合
        pass_preg(data, top, end) {
            let preg = new RegExp('^((?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*()\\[\\]{}\\-=_+|;:,.<>\/?])(?!.*\\s).{' + top + ',' + end + '})$');
            return preg.test(data);
        },
        //验证身份证位数
        is_card(txt) {
            return txt.toString().length == 15 || txt.toString().length == 18
        },
        //是否包含数字
        in_int(data) {
            return /\d/g.test(data)
        },
        //是否包含大写
        is_upper(data) {
            return /[A-Z]+/g.test(data)
        },
        //是否包含小写
        is_lower(data) {
            return /[a-z]+/g.test(data)
        },
        //是否包含汉字
        is_chinese(data) {
            return /[\u4E00-\u9FA5]+/g.test(data)
        },
        //验证邮箱
        is_mail(data) {
            return /\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/.test(data)
        },
        //验证手机
        is_mobile(data) {
            return /^1([3456789])\d{9}$/.test(data)
        },
        //验证姓名
        is_name(data) {
            return /^[\u4E00-\u9FA5]{2,4}$/.test(data)
        },
        //验证帐号
        is_user(data, top, end, type) {
            return (new RegExp((type ? "^[A-Za-z0-9\x20-\x7f]{" + (top || 4) + "," + (end || 10) + "}$" : "^[A-Za-z0-9]{" + (top || 4) + "," + (end || 10) + "}$"))).test(data);
        },
        //验证密码 (密码,最小位数,最大位数,是否支持符号,array|data|code)
        is_pass(a, e, t, d, o) {
            let r, c, h, A = 0, C = 0, f = !1, l = !1, i = !1, n = !1, s = 0,
                g = [{code: 0, data: "密码须含数字/字母"}, {code: 1, data: "密码内太多重复字串"}, {
                    code: 2,
                    data: "密码不可使用空白非英文语系的字"
                }, {code: 3, data: "密码中太多顺序字串"}, {
                    code: 4,
                    data: "密码由" + (e || 6) + "-" + (t || 6) + "位数字/字母" + (d ? "/符号" : "") + "组成"
                }, {code: 5, data: "密码不能带有符号"}, {code: 6, data: "密码最少包含一个字母"}, {
                    code: 7,
                    data: "密码安全程序底"
                }, {
                    code: 8,
                    data: "密码安全程序中"
                }, {code: 9, data: "密码安全程序高"}];
            if (/^(?![^a-zA-Z]+$)(?!\D+$)/.test(a) && ((a.length < (e || 6) || a.length > (t || 16)) && (s = 4), d || /^[a-zA-Z0-9]{1,}$/.test(a) || (s = 5), !s)) {
                for (r = 0; r < a.length; r++) if ((c = a.split(a.substr(r, 1))).length > 4) {
                    for (C = 0, h = 0; h < c.length; h++) 0 === c[h].length && h > 0 && C++;
                    if (C >= 3) {
                        A = 1;
                        break
                    }
                } else if (a.charCodeAt(r) >= 33 && a.charCodeAt(r) <= 47 || a.charCodeAt(r) >= 58 && a.charCodeAt(r) <= 64 || a.charCodeAt(r) >= 91 && a.charCodeAt(r) <= 96 || a.charCodeAt(r) >= 123 && a.charCodeAt(r) <= 126) A += 10, f = !0; else if (a.charCodeAt(r) >= 48 && a.charCodeAt(r) <= 57) A += 4, l = !0; else {
                    if (!(a.charCodeAt(r) >= 65 && a.charCodeAt(r) <= 90 || a.charCodeAt(r) >= 97 && a.charCodeAt(r) <= 122)) {
                        A = 2;
                        break
                    }
                    A += 7, a.charCodeAt(r) >= 65 && a.charCodeAt(r) <= 90 ? n = !0 : i = !0
                }
                if (A > 0) {
                    if ((f && l || f && n || f && i) && (A += 10), (l && n || l && i) && (A += 8), n && i && (A += 8), a.length >= 4) for (r = 0; r < a.length - 3; r++) if ((a.charCodeAt(r) >= 48 && a.charCodeAt(r) <= 57 || a.charCodeAt(r) >= 65 && a.charCodeAt(r) <= 90 || a.charCodeAt(r) >= 97 && a.charCodeAt(r) <= 122) && 1 === Math.abs(a.charCodeAt(r) - a.charCodeAt(r + 1)) && 1 === Math.abs(a.charCodeAt(r + 1) - a.charCodeAt(r + 2)) && 1 === Math.abs(a.charCodeAt(r + 2) - a.charCodeAt(r + 3))) {
                        A = 3;
                        break
                    }
                    A = A > 100 ? 100 : 0 > A ? 0 : A
                }
                s = A
            }
            return 32 === s ? s = 6 : s > 5 && s <= 45 ? s = 7 : s > 45 && s <= 59 ? s = 8 : s > 59 && (s = 9), "array" === (o || "array") ? g[s] : g[s][o]
        }
    };
};
window.alone_check = aloneCheck();