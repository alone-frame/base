window.aloneSafe = function () {
    const then = {}
    const self = {};
    const crypto = aloneCrypto();
    then.rand = () => {
        const rand = (Math.random() * 10000000).toString(16), time = Math.random().toString();
        return rand.substring(0, 4) + (new Date()).getTime() + time.substring(2, 7)
    }
    then.to_json = (data) => {
        try {
            return typeof data === "object" ? JSON.stringify(data) : data;
        } catch (e) {
            return data;
        }
    }
    then.to_obj = (data) => {
        try {
            return typeof data === "string" ? JSON.parse(data) : data;
        } catch (e) {
            return data;
        }
    }
    then.mov = (name) => {
        const data = {aes: [0, 1, 2, 3], des: [4, 5], des3: [6, 7, 8, 9]};
        return data[name];
    }
    then.mov_rand = (name) => {
        const arr = then.mov(name);
        return arr[Math.round(Math.random() * (arr.length - 1))];
    }
    self.md5 = (data) => {
        return crypto.MD5(data).toString()
    }
    self.sha1 = (data) => {
        return crypto.SHA1(data).toString()
    }
    self.sha3 = (data) => {
        return crypto.SHA3(data).toString()
    }
    self.sha256 = (data) => {
        return crypto.SHA256(data).toString()
    }
    self.sha512 = (data) => {
        return crypto.SHA512(data).toString()
    }
    self.base64 = {
        encrypt(data) {
            return crypto.enc.Base64.stringify(crypto.enc.Utf8.parse(data))
        },
        decrypt(data) {
            return crypto.enc.Base64.parse(data).toString(crypto.enc.Utf8)
        }
    }
    self.aes = {
        encrypt(data, key, iv) {
            try {
                return crypto.AES.encrypt(crypto.enc.Utf8.parse(data)
                    , crypto.enc.Utf8.parse(key)
                    , ((iv || '') ? {
                        iv: crypto.enc.Utf8.parse(iv),
                        mode: crypto.mode.CBC,
                        padding: crypto.pad.Pkcs7
                    } : {
                        mode: crypto.mode.ECB,
                        padding: crypto.pad.Pkcs7
                    })).toString();
            } catch (e) {
                return false
            }
        },
        decrypt(data, key, iv) {
            try {
                return crypto.AES.decrypt(data
                    , crypto.enc.Utf8.parse(key)
                    , ((iv || '') ? {
                        iv: crypto.enc.Utf8.parse(iv),
                        mode: crypto.mode.CBC,
                        padding: crypto.pad.Pkcs7
                    } : {
                        mode: crypto.mode.ECB,
                        padding: crypto.pad.Pkcs7
                    })).toString(crypto.enc.Utf8)
            } catch (e) {
                return false
            }
        },
        en(data) {
            const rand = then.rand().toString(),
                iv = (Math.floor(Math.random() * 17)),
                md5 = (self.md5(rand)).toString(),
                type = Math.ceil(Math.random() * 2),
                type_md5 = self.md5(md5),
                prefix = then.mov_rand('aes');
            return {
                random: (prefix.toString() + type + rand + (iv.toString().length === 1 ? '0' + iv : iv)).toString(),
                data: self.aes.encrypt((typeof (data) === 'object' ? then.to_json(data) : data), md5.substring(iv, iv + 16), (type == 2 ? (type_md5.substring(iv, iv + 16)) : ''))
            }
        },
        de(data, random) {
            if ((typeof (data) === 'object')) {
                random = data.random || random;
                data = data.data || data
            }
            const md5 = self.md5(random.substring(2, random.length - 2)),
                type = random.substring(1, 2),
                iv = parseInt(random.substring(random.length - 2)),
                type_md5 = self.md5(md5)
            return then.to_obj(self.aes.decrypt(data, md5.substring(iv, iv + 16), (type == 2 ? (type_md5.substring(iv, iv + 16)) : '')))
        }
    }
    self.des = {
        encrypt(data, key, iv) {
            try {
                return crypto.DES.encrypt(crypto.enc.Utf8.parse(data)
                    , crypto.enc.Utf8.parse(key)
                    , ((iv || '') ? {
                        iv: crypto.enc.Utf8.parse(iv),
                        mode: crypto.mode.CBC,
                        padding: crypto.pad.Pkcs7
                    } : {
                        mode: crypto.mode.ECB,
                        padding: crypto.pad.Pkcs7
                    })).toString()
            } catch (e) {
                return false
            }
        },
        decrypt(data, key, iv) {
            try {
                return crypto.DES.decrypt(data
                    , crypto.enc.Utf8.parse(key)
                    , ((iv || '') ? {
                        iv: crypto.enc.Utf8.parse(iv),
                        mode: crypto.mode.CBC,
                        padding: crypto.pad.Pkcs7
                    } : {
                        mode: crypto.mode.ECB,
                        padding: crypto.pad.Pkcs7
                    })).toString(crypto.enc.Utf8);
            } catch (e) {
                return false
            }
        },
        en: (data) => {
            const rand = then.rand().toString(),
                iv = (Math.floor(Math.random() * 9)),
                md5 = (self.md5(rand)).toString(),
                type = Math.ceil(Math.random() * 2),
                type_md5 = self.md5(md5),
                prefix = then.mov_rand('des');
            return {
                random: (prefix.toString() + type + rand + (iv.toString().length === 1 ? '0' + iv : iv)).toString(),
                data: self.des.encrypt((typeof (data) === 'object' ? then.to_json(data) : data), md5.substring(iv, iv + 8), (type == 2 ? (type_md5.substring(iv, iv + 8)) : ''))
            }
        },
        de(data, random) {
            if ((typeof (data) === 'object')) {
                random = data.random || random;
                data = data.data || data
            }
            const md5 = self.md5(random.substring(2, (random.length - 2))),
                type = random.substring(1, 2),
                iv = parseInt(random.substring(random.length - 2)),
                type_md5 = self.md5(md5);
            return then.to_obj(self.des.decrypt(data, md5.substring(iv, iv + 8), (type == 2 ? (type_md5.substring(iv, iv + 8)) : '')))
        }
    }
    self.des3 = {
        encrypt(data, key, iv) {
            try {
                return crypto.TripleDES.encrypt(crypto.enc.Utf8.parse(data)
                    , crypto.enc.Utf8.parse(key)
                    , ((iv || '') ? {
                        iv: crypto.enc.Utf8.parse(iv),
                        mode: crypto.mode.CBC,
                        padding: crypto.pad.Pkcs7
                    } : {
                        mode: crypto.mode.ECB,
                        padding: crypto.pad.Pkcs7
                    })).toString()
            } catch (e) {
                return false
            }
        },
        decrypt(data, key, iv) {
            try {
                return crypto.TripleDES.decrypt(data
                    , crypto.enc.Utf8.parse(key)
                    , ((iv || '') ? {
                        iv: crypto.enc.Utf8.parse(iv),
                        mode: crypto.mode.CBC,
                        padding: crypto.pad.Pkcs7
                    } : {
                        mode: crypto.mode.ECB,
                        padding: crypto.pad.Pkcs7
                    })).toString(crypto.enc.Utf8)
            } catch (e) {
                return false
            }
        },
        en(data) {
            const rand = then.rand().toString(),
                iv = (Math.floor(Math.random() * 9)),
                md5 = (self.md5(rand)).toString(),
                type = Math.ceil(Math.random() * 2),
                type_md5 = self.md5(md5),
                prefix = then.mov_rand('des3');
            return {
                random: (prefix.toString() + type + rand + (iv.toString().length === 1 ? '0' + iv : iv)).toString(),
                data: self.des3.encrypt((typeof (data) === 'object' ? then.to_json(data) : data), md5.substring(iv, iv + 24), (type == 2 ? (type_md5.substring(iv, iv + 8)) : ''))
            }
        },
        de(data, random) {
            if ((typeof (data) === 'object')) {
                random = data.random || random;
                data = data.data || data
            }
            const md5 = self.md5(random.substring(2, (random.length - 2))),
                type = random.substring(1, 2),
                iv = parseInt(random.substring(random.length - 2)),
                type_md5 = self.md5(md5)
            return then.to_obj(self.des3.decrypt(data, md5.substring(iv, iv + 24), (type == 2 ? (type_md5.substring(iv, iv + 8)) : '')))
        }
    }
    //动态加密解密
    self.mov = {
        en(data, mode) {
            mode = mode || ['aes', 'des', 'des3'];
            const type = mode[Math.round(Math.random() * (mode.length - 1))];
            if (type == 'aes') {
                return self.aes.en(data)
            } else if (type == 'des') {
                return self.des.en(data)
            }
            return self.des3.en(data)
        },
        de(data, random) {
            if ((typeof (data) === 'object')) {
                random = data.random || random;
                data = data.data || data
            }
            const type = parseInt(random.substring(0, 1));
            if (then.mov('aes').indexOf(type) >= 0) {
                return self.aes.de(data, random)
            } else if (then.mov('des').indexOf(type) >= 0) {
                return self.des.de(data, random)
            }
            return self.des3.de(data, random)
        }
    }
    //生成到url参数使用,加密和解密
    self.url = {
        en(data, mode) {
            let json = self.mov.en(data, mode);
            let string = then.to_json(json);
            let base = self.base64.encrypt(string);
            return base.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
        },
        de(data) {
            let base = data.replace(/\\-/g, '+').replace(/\\_/g, '/');
            let string = self.base64.decrypt(base);
            let json = then.to_obj(string);
            return self.mov.de(json)
        }
    }
    self.crypto = crypto;
    return self;
};

/**
 * @typedef {Object} safe_prompt
 * @property {Object} crypto
 * @property {Function} md5
 * @property {Function} sha1
 * @property {Function} sha3
 * @property {Function} sha256
 * @property {Function} sha512
 * @property {Object} mov
 * @property {Function} mov.en
 * @property {Function} mov.de
 * @property {Object} url
 * @property {Function} url.en
 * @property {Function} url.de
 * @property {Object} base64
 * @property {Function} base64.encrypt
 * @property {Function} base64.decrypt
 * @property {Object} aes
 * @property {Function} aes.encrypt
 * @property {Function} aes.decrypt
 * @property {Object} des
 * @property {Function} des.encrypt
 * @property {Function} des.decrypt
 * @property {Object} des3
 * @property {Function} des3.encrypt
 * @property {Function} des3.decrypt
 */
window.alone_safe = /** @type {safe_prompt} */aloneSafe();