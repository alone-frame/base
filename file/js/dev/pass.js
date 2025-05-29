window.alonePass = function () {
    const self = {}, open = {}, a = 8, i = "=", e = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    self.g = function (t) {
        for (var e = "", n = 0; n < 4 * t.length; n += 3) for (var r = (t[n >> 2] >> 8 * (3 - n % 4) & 255) << 16 | (t[n + 1 >> 2] >> 8 * (3 - (n + 1) % 4) & 255) << 8 | t[n + 2 >> 2] >> 8 * (3 - (n + 2) % 4) & 255, a = 0; a < 4; a++) 8 * n + 6 * a > 32 * t.length ? e += i : e += "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/".charAt(r >> 6 * (3 - a) & 63);
        return e
    }
    self.h = function (t) {
        for (var e = Array(), n = (1 << a) - 1, r = 0; r < t.length * a; r += a) e[r >> 5] |= (t.charCodeAt(r / a) & n) << 32 - a - r % 32;
        return e
    }
    self.o = function (t, e) {
        t[e >> 5] |= 128 << 24 - e % 32, t[15 + (e + 64 >> 9 << 4)] = e;
        for (var n = Array(80), r = 1732584193, i = -271733879, a = -1732584194, o = 271733878, h = -1009589776, g = 0; g < t.length; g += 16) {
            for (var f = r, I = i, l = a, y = o, m = h, p = 0; p < 80; p++) {
                n[p] = p < 16 ? t[g + p] : self.u(n[p - 3] ^ n[p - 8] ^ n[p - 14] ^ n[p - 16], 1);
                var v = self.s(self.s(self.u(r, 5), self.d(p, i, a, o)), self.s(self.s(h, n[p]), self.c(p)));
                h = o, o = a, a = self.u(i, 30), i = r, r = v
            }
            r = self.s(r, f), i = self.s(i, I), a = self.s(a, l), o = self.s(o, y), h = self.s(h, m)
        }
        return Array(r, i, a, o, h)
    }

    self.c = function (t) {
        return t < 20 ? 1518500249 : t < 40 ? 1859775393 : t < 60 ? -1894007588 : -899497514
    }

    self.d = function (t, e, n, r) {
        return t < 20 ? e & n | ~e & r : t < 40 ? e ^ n ^ r : t < 60 ? e & n | e & r | n & r : e ^ n ^ r
    }

    self.u = function (t, e) {
        return t << e | t >>> 32 - e
    }

    self.s = function (t, e) {
        var n = (65535 & t) + (65535 & e);
        return (t >> 16) + (e >> 16) + (n >> 16) << 16 | 65535 & n
    }

    self.a = function (e) {
        var r = 0;
        for (var t = r ? "0123456789ABCDEF" : "0123456789abcdef", o = "", i = 0; i < 4 * e.length; i++) o += t.charAt(e[i >> 2] >> 8 * (3 - i % 4) + 4 & 15) + t.charAt(e[i >> 2] >> 8 * (3 - i % 4) & 15);
        return o
    }
    self.encodeBase64 = function (t) {
        var n, r, i, a, o, d, c, s = "", u = 0;
        for (t = function (t) {
            t = t.replace(/\r\n/g, "\n");
            for (var e = "", n = 0; n < t.length; n++) {
                var r = t.charCodeAt(n);
                r < 128 ? e += String.fromCharCode(r) : r > 127 && r < 2048 ? (e += String.fromCharCode(r >> 6 | 192), e += String.fromCharCode(63 & r | 128)) : (e += String.fromCharCode(r >> 12 | 224), e += String.fromCharCode(r >> 6 & 63 | 128), e += String.fromCharCode(63 & r | 128))
            }
            return e
        }(t); u < t.length;) a = (n = t.charCodeAt(u++)) >> 2, o = (3 & n) << 4 | (r = t.charCodeAt(u++)) >> 4, d = (15 & r) << 2 | (i = t.charCodeAt(u++)) >> 6, c = 63 & i, isNaN(r) ? d = c = 64 : isNaN(i) && (c = 64), s = s + e.charAt(a) + e.charAt(o) + e.charAt(d) + e.charAt(c);
        return s
    }

    self.getTime = function () {
        return (new Date).getTime()
    }
    self.randTime = function () {
        return "" + self.getTime().toString().slice(2) + Math.floor(900 * Math.random() + 100)
    }
    open.encodeSha1 = function (e) {
        return self.g(self.o(self.h(e), e.length * a));
    }
    open.encode = function (e) {
        return open.encodeSha1(open.encodeSha1(e))
    }
    open.encodePsw = function (e) {
        var n = self.getTime();
        return {salt: n, token: open.encodeSha1(open.encodeSha1(e) + n)}
    }
    open.encodeSha2 = function (e) {
        return self.a(self.o(self.h(e), e.length * a));
    }
    open.encode2 = function (e) {
        return open.encodeSha2(open.encodeSha2(e))
    }
    open.encodePsw2 = function (e) {
        var n = self.getTime();
        return {salt: n, token: open.encodeSha2(open.encodeSha2(e) + n)}
    }
    return open;
};
window.alone_pass = alonePass();