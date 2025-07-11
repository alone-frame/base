window.alone_qrcode = function (elem, text, {utf8, render, width, height, background, foreground, correctLevel} = {}) {
    !function (t) {
        t.fn.qrcode = function (_) {
            var e;

            function r(t) {
                this.mode = e, this.data = t
            }

            function o(t, _) {
                this.typeNumber = t, this.errorCorrectLevel = _, this.modules = null, this.moduleCount = 0, this.dataCache = null, this.dataList = []
            }

            function n(t, _) {
                if (null == t.length) throw Error(t.length + "/" + _);
                for (var e = 0; e < t.length && 0 == t[e];) e++;
                this.num = Array(t.length - e + _);
                for (var r = 0; r < t.length - e; r++) this.num[r] = t[r + e]
            }

            function i(t, _) {
                this.totalCount = t, this.dataCount = _
            }

            function s() {
                this.buffer = [], this.length = 0
            }

            r.prototype = {
                getLength: function () {
                    return this.data.length
                }, write: function (t) {
                    for (var _ = 0; _ < this.data.length; _++) t.put(this.data.charCodeAt(_), 8)
                }
            }, o.prototype = {
                addData: function (t) {
                    this.dataList.push(new r(t)), this.dataCache = null
                }, isDark: function (t, _) {
                    if (0 > t || this.moduleCount <= t || 0 > _ || this.moduleCount <= _) throw Error(t + "," + _);
                    return this.modules[t][_]
                }, getModuleCount: function () {
                    return this.moduleCount
                }, make: function () {
                    if (1 > this.typeNumber) {
                        var t = 1;
                        for (t = 1; 40 > t; t++) {
                            for (var _ = i.getRSBlocks(t, this.errorCorrectLevel), e = new s, r = 0, o = 0; o < _.length; o++) r += _[o].dataCount;
                            for (o = 0; o < this.dataList.length; o++) _ = this.dataList[o], e.put(_.mode, 4), e.put(_.getLength(), u.getLengthInBits(_.mode, t)), _.write(e);
                            if (e.getLengthInBits() <= 8 * r) break
                        }
                        this.typeNumber = t
                    }
                    this.makeImpl(!1, this.getBestMaskPattern())
                }, makeImpl: function (t, _) {
                    this.moduleCount = 4 * this.typeNumber + 17, this.modules = Array(this.moduleCount);
                    for (var e = 0; e < this.moduleCount; e++) {
                        this.modules[e] = Array(this.moduleCount);
                        for (var r = 0; r < this.moduleCount; r++) this.modules[e][r] = null
                    }
                    this.setupPositionProbePattern(0, 0), this.setupPositionProbePattern(this.moduleCount - 7, 0), this.setupPositionProbePattern(0, this.moduleCount - 7), this.setupPositionAdjustPattern(), this.setupTimingPattern(), this.setupTypeInfo(t, _), 7 <= this.typeNumber && this.setupTypeNumber(t), null == this.dataCache && (this.dataCache = o.createData(this.typeNumber, this.errorCorrectLevel, this.dataList)), this.mapData(this.dataCache, _)
                }, setupPositionProbePattern: function (t, _) {
                    for (var e = -1; 7 >= e; e++) if (!(-1 >= t + e || this.moduleCount <= t + e)) for (var r = -1; 7 >= r; r++) -1 >= _ + r || this.moduleCount <= _ + r || (this.modules[t + e][_ + r] = 0 <= e && 6 >= e && (0 == r || 6 == r) || 0 <= r && 6 >= r && (0 == e || 6 == e) || 2 <= e && 4 >= e && 2 <= r && 4 >= r)
                }, getBestMaskPattern: function () {
                    for (var t = 0, _ = 0, e = 0; 8 > e; e++) {
                        this.makeImpl(!0, e);
                        var r = u.getLostPoint(this);
                        (0 == e || t > r) && (t = r, _ = e)
                    }
                    return _
                }, createMovieClip: function (t, _, e) {
                    for (t = t.createEmptyMovieClip(_, e), this.make(), _ = 0; _ < this.modules.length; _++) {
                        e = 1 * _;
                        for (var r = 0; r < this.modules[_].length; r++) {
                            var o = 1 * r;
                            this.modules[_][r] && (t.beginFill(0, 100), t.moveTo(o, e), t.lineTo(o + 1, e), t.lineTo(o + 1, e + 1), t.lineTo(o, e + 1), t.endFill())
                        }
                    }
                    return t
                }, setupTimingPattern: function () {
                    for (var t = 8; t < this.moduleCount - 8; t++) null == this.modules[t][6] && (this.modules[t][6] = 0 == t % 2);
                    for (t = 8; t < this.moduleCount - 8; t++) null == this.modules[6][t] && (this.modules[6][t] = 0 == t % 2)
                }, setupPositionAdjustPattern: function () {
                    for (var t = u.getPatternPosition(this.typeNumber), _ = 0; _ < t.length; _++) for (var e = 0; e < t.length; e++) {
                        var r = t[_], o = t[e];
                        if (null == this.modules[r][o]) for (var n = -2; 2 >= n; n++) for (var i = -2; 2 >= i; i++) this.modules[r + n][o + i] = -2 == n || 2 == n || -2 == i || 2 == i || 0 == n && 0 == i
                    }
                }, setupTypeNumber: function (t) {
                    for (var _ = u.getBCHTypeNumber(this.typeNumber), e = 0; 18 > e; e++) {
                        var r = !t && 1 == (_ >> e & 1);
                        this.modules[Math.floor(e / 3)][e % 3 + this.moduleCount - 8 - 3] = r
                    }
                    for (e = 0; 18 > e; e++) r = !t && 1 == (_ >> e & 1), this.modules[e % 3 + this.moduleCount - 8 - 3][Math.floor(e / 3)] = r
                }, setupTypeInfo: function (t, _) {
                    for (var e = u.getBCHTypeInfo(this.errorCorrectLevel << 3 | _), r = 0; 15 > r; r++) {
                        var o = !t && 1 == (e >> r & 1);
                        6 > r ? this.modules[r][8] = o : 8 > r ? this.modules[r + 1][8] = o : this.modules[this.moduleCount - 15 + r][8] = o
                    }
                    for (r = 0; 15 > r; r++) o = !t && 1 == (e >> r & 1), 8 > r ? this.modules[8][this.moduleCount - r - 1] = o : 9 > r ? this.modules[8][15 - r - 1 + 1] = o : this.modules[8][15 - r - 1] = o;
                    this.modules[this.moduleCount - 8][8] = !t
                }, mapData: function (t, _) {
                    for (var e = -1, r = this.moduleCount - 1, o = 7, n = 0, i = this.moduleCount - 1; 0 < i; i -= 2) for (6 == i && i--; ;) {
                        for (var s = 0; 2 > s; s++) if (null == this.modules[r][i - s]) {
                            var a = !1;
                            n < t.length && (a = 1 == (t[n] >>> o & 1)), u.getMask(_, r, i - s) && (a = !a), this.modules[r][i - s] = a, -1 == --o && (n++, o = 7)
                        }
                        if (0 > (r += e) || this.moduleCount <= r) {
                            r -= e, e = -e;
                            break
                        }
                    }
                }
            }, o.PAD0 = 236, o.PAD1 = 17, o.createData = function (t, _, e) {
                _ = i.getRSBlocks(t, _);
                for (var r = new s, n = 0; n < e.length; n++) {
                    var a = e[n];
                    r.put(a.mode, 4), r.put(a.getLength(), u.getLengthInBits(a.mode, t)), a.write(r)
                }
                for (n = t = 0; n < _.length; n++) t += _[n].dataCount;
                if (r.getLengthInBits() > 8 * t) throw Error("code length overflow. (" + r.getLengthInBits() + ">" + 8 * t + ")");
                for (r.getLengthInBits() + 4 <= 8 * t && r.put(0, 4); 0 != r.getLengthInBits() % 8;) r.putBit(!1);
                for (; !(r.getLengthInBits() >= 8 * t || (r.put(o.PAD0, 8), r.getLengthInBits() >= 8 * t));) r.put(o.PAD1, 8);
                return o.createBytes(r, _)
            }, o.createBytes = function (t, _) {
                for (var e = 0, r = 0, o = 0, i = Array(_.length), s = Array(_.length), a = 0; a < _.length; a++) {
                    var h = _[a].dataCount, l = _[a].totalCount - h;
                    r = Math.max(r, h), o = Math.max(o, l), i[a] = Array(h);
                    for (var g = 0; g < i[a].length; g++) i[a][g] = 255 & t.buffer[g + e];
                    for (e += h, g = u.getErrorCorrectPolynomial(l), h = new n(i[a], g.getLength() - 1).mod(g), s[a] = Array(g.getLength() - 1), g = 0; g < s[a].length; g++) l = g + h.getLength() - s[a].length, s[a][g] = 0 <= l ? h.get(l) : 0
                }
                for (g = a = 0; g < _.length; g++) a += _[g].totalCount;
                for (e = Array(a), g = h = 0; g < r; g++) for (a = 0; a < _.length; a++) g < i[a].length && (e[h++] = i[a][g]);
                for (g = 0; g < o; g++) for (a = 0; a < _.length; a++) g < s[a].length && (e[h++] = s[a][g]);
                return e
            }, e = 4;
            for (var u = {
                PATTERN_POSITION_TABLE: [[], [6, 18], [6, 22], [6, 26], [6, 30], [6, 34], [6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50], [6, 30, 54], [6, 32, 58], [6, 34, 62], [6, 26, 46, 66], [6, 26, 48, 70], [6, 26, 50, 74], [6, 30, 54, 78], [6, 30, 56, 82], [6, 30, 58, 86], [6, 34, 62, 90], [6, 28, 50, 72, 94], [6, 26, 50, 74, 98], [6, 30, 54, 78, 102], [6, 28, 54, 80, 106], [6, 32, 58, 84, 110], [6, 30, 58, 86, 114], [6, 34, 62, 90, 118], [6, 26, 50, 74, 98, 122], [6, 30, 54, 78, 102, 126], [6, 26, 52, 78, 104, 130], [6, 30, 56, 82, 108, 134], [6, 34, 60, 86, 112, 138], [6, 30, 58, 86, 114, 142], [6, 34, 62, 90, 118, 146], [6, 30, 54, 78, 102, 126, 150], [6, 24, 50, 76, 102, 128, 154], [6, 28, 54, 80, 106, 132, 158], [6, 32, 58, 84, 110, 136, 162], [6, 26, 54, 82, 110, 138, 166], [6, 30, 58, 86, 114, 142, 170]],
                G15: 1335,
                G18: 7973,
                G15_MASK: 21522,
                getBCHTypeInfo: function (t) {
                    for (var _ = t << 10; 0 <= u.getBCHDigit(_) - u.getBCHDigit(u.G15);) _ ^= u.G15 << u.getBCHDigit(_) - u.getBCHDigit(u.G15);
                    return (t << 10 | _) ^ u.G15_MASK
                },
                getBCHTypeNumber: function (t) {
                    for (var _ = t << 12; 0 <= u.getBCHDigit(_) - u.getBCHDigit(u.G18);) _ ^= u.G18 << u.getBCHDigit(_) - u.getBCHDigit(u.G18);
                    return t << 12 | _
                },
                getBCHDigit: function (t) {
                    for (var _ = 0; 0 != t;) _++, t >>>= 1;
                    return _
                },
                getPatternPosition: function (t) {
                    return u.PATTERN_POSITION_TABLE[t - 1]
                },
                getMask: function (t, _, e) {
                    switch (t) {
                        case 0:
                            return 0 == (_ + e) % 2;
                        case 1:
                            return 0 == _ % 2;
                        case 2:
                            return 0 == e % 3;
                        case 3:
                            return 0 == (_ + e) % 3;
                        case 4:
                            return 0 == (Math.floor(_ / 2) + Math.floor(e / 3)) % 2;
                        case 5:
                            return 0 == _ * e % 2 + _ * e % 3;
                        case 6:
                            return 0 == (_ * e % 2 + _ * e % 3) % 2;
                        case 7:
                            return 0 == (_ * e % 3 + (_ + e) % 2) % 2;
                        default:
                            throw Error("bad maskPattern:" + t)
                    }
                },
                getErrorCorrectPolynomial: function (t) {
                    for (var _ = new n([1], 0), e = 0; e < t; e++) _ = _.multiply(new n([1, a.gexp(e)], 0));
                    return _
                },
                getLengthInBits: function (t, _) {
                    if (1 <= _ && 10 > _) switch (t) {
                        case 1:
                            return 10;
                        case 2:
                            return 9;
                        case e:
                        case 8:
                            return 8;
                        default:
                            throw Error("mode:" + t)
                    } else if (27 > _) switch (t) {
                        case 1:
                            return 12;
                        case 2:
                            return 11;
                        case e:
                            return 16;
                        case 8:
                            return 10;
                        default:
                            throw Error("mode:" + t)
                    } else {
                        if (!(41 > _)) throw Error("type:" + _);
                        switch (t) {
                            case 1:
                                return 14;
                            case 2:
                                return 13;
                            case e:
                                return 16;
                            case 8:
                                return 12;
                            default:
                                throw Error("mode:" + t)
                        }
                    }
                },
                getLostPoint: function (t) {
                    for (var _ = t.getModuleCount(), e = 0, r = 0; r < _; r++) for (var o = 0; o < _; o++) {
                        for (var n = 0, i = t.isDark(r, o), s = -1; 1 >= s; s++) if (!(0 > r + s || _ <= r + s)) for (var u = -1; 1 >= u; u++) 0 > o + u || _ <= o + u || 0 == s && 0 == u || i == t.isDark(r + s, o + u) && n++;
                        5 < n && (e += 3 + n - 5)
                    }
                    for (r = 0; r < _ - 1; r++) for (o = 0; o < _ - 1; o++) n = 0, t.isDark(r, o) && n++, t.isDark(r + 1, o) && n++, t.isDark(r, o + 1) && n++, t.isDark(r + 1, o + 1) && n++, (0 == n || 4 == n) && (e += 3);
                    for (r = 0; r < _; r++) for (o = 0; o < _ - 6; o++) t.isDark(r, o) && !t.isDark(r, o + 1) && t.isDark(r, o + 2) && t.isDark(r, o + 3) && t.isDark(r, o + 4) && !t.isDark(r, o + 5) && t.isDark(r, o + 6) && (e += 40);
                    for (o = 0; o < _; o++) for (r = 0; r < _ - 6; r++) t.isDark(r, o) && !t.isDark(r + 1, o) && t.isDark(r + 2, o) && t.isDark(r + 3, o) && t.isDark(r + 4, o) && !t.isDark(r + 5, o) && t.isDark(r + 6, o) && (e += 40);
                    for (o = n = 0; o < _; o++) for (r = 0; r < _; r++) t.isDark(r, o) && n++;
                    return e + Math.abs(100 * n / _ / _ - 50) / 5 * 10
                }
            }, a = {
                glog: function (t) {
                    if (1 > t) throw Error("glog(" + t + ")");
                    return a.LOG_TABLE[t]
                }, gexp: function (t) {
                    for (; 0 > t;) t += 255;
                    for (; 256 <= t;) t -= 255;
                    return a.EXP_TABLE[t]
                }, EXP_TABLE: Array(256), LOG_TABLE: Array(256)
            }, h = 0; 8 > h; h++) a.EXP_TABLE[h] = 1 << h;
            for (h = 8; 256 > h; h++) a.EXP_TABLE[h] = a.EXP_TABLE[h - 4] ^ a.EXP_TABLE[h - 5] ^ a.EXP_TABLE[h - 6] ^ a.EXP_TABLE[h - 8];
            for (h = 0; 255 > h; h++) a.LOG_TABLE[a.EXP_TABLE[h]] = h;
            return n.prototype = {
                get: function (t) {
                    return this.num[t]
                }, getLength: function () {
                    return this.num.length
                }, multiply: function (t) {
                    for (var _ = Array(this.getLength() + t.getLength() - 1), e = 0; e < this.getLength(); e++) for (var r = 0; r < t.getLength(); r++) _[e + r] ^= a.gexp(a.glog(this.get(e)) + a.glog(t.get(r)));
                    return new n(_, 0)
                }, mod: function (t) {
                    if (0 > this.getLength() - t.getLength()) return this;
                    for (var _ = a.glog(this.get(0)) - a.glog(t.get(0)), e = Array(this.getLength()), r = 0; r < this.getLength(); r++) e[r] = this.get(r);
                    for (r = 0; r < t.getLength(); r++) e[r] ^= a.gexp(a.glog(t.get(r)) + _);
                    return new n(e, 0).mod(t)
                }
            }, i.RS_BLOCK_TABLE = [[1, 26, 19], [1, 26, 16], [1, 26, 13], [1, 26, 9], [1, 44, 34], [1, 44, 28], [1, 44, 22], [1, 44, 16], [1, 70, 55], [1, 70, 44], [2, 35, 17], [2, 35, 13], [1, 100, 80], [2, 50, 32], [2, 50, 24], [4, 25, 9], [1, 134, 108], [2, 67, 43], [2, 33, 15, 2, 34, 16], [2, 33, 11, 2, 34, 12], [2, 86, 68], [4, 43, 27], [4, 43, 19], [4, 43, 15], [2, 98, 78], [4, 49, 31], [2, 32, 14, 4, 33, 15], [4, 39, 13, 1, 40, 14], [2, 121, 97], [2, 60, 38, 2, 61, 39], [4, 40, 18, 2, 41, 19], [4, 40, 14, 2, 41, 15], [2, 146, 116], [3, 58, 36, 2, 59, 37], [4, 36, 16, 4, 37, 17], [4, 36, 12, 4, 37, 13], [2, 86, 68, 2, 87, 69], [4, 69, 43, 1, 70, 44], [6, 43, 19, 2, 44, 20], [6, 43, 15, 2, 44, 16], [4, 101, 81], [1, 80, 50, 4, 81, 51], [4, 50, 22, 4, 51, 23], [3, 36, 12, 8, 37, 13], [2, 116, 92, 2, 117, 93], [6, 58, 36, 2, 59, 37], [4, 46, 20, 6, 47, 21], [7, 42, 14, 4, 43, 15], [4, 133, 107], [8, 59, 37, 1, 60, 38], [8, 44, 20, 4, 45, 21], [12, 33, 11, 4, 34, 12], [3, 145, 115, 1, 146, 116], [4, 64, 40, 5, 65, 41], [11, 36, 16, 5, 37, 17], [11, 36, 12, 5, 37, 13], [5, 109, 87, 1, 110, 88], [5, 65, 41, 5, 66, 42], [5, 54, 24, 7, 55, 25], [11, 36, 12], [5, 122, 98, 1, 123, 99], [7, 73, 45, 3, 74, 46], [15, 43, 19, 2, 44, 20], [3, 45, 15, 13, 46, 16], [1, 135, 107, 5, 136, 108], [10, 74, 46, 1, 75, 47], [1, 50, 22, 15, 51, 23], [2, 42, 14, 17, 43, 15], [5, 150, 120, 1, 151, 121], [9, 69, 43, 4, 70, 44], [17, 50, 22, 1, 51, 23], [2, 42, 14, 19, 43, 15], [3, 141, 113, 4, 142, 114], [3, 70, 44, 11, 71, 45], [17, 47, 21, 4, 48, 22], [9, 39, 13, 16, 40, 14], [3, 135, 107, 5, 136, 108], [3, 67, 41, 13, 68, 42], [15, 54, 24, 5, 55, 25], [15, 43, 15, 10, 44, 16], [4, 144, 116, 4, 145, 117], [17, 68, 42], [17, 50, 22, 6, 51, 23], [19, 46, 16, 6, 47, 17], [2, 139, 111, 7, 140, 112], [17, 74, 46], [7, 54, 24, 16, 55, 25], [34, 37, 13], [4, 151, 121, 5, 152, 122], [4, 75, 47, 14, 76, 48], [11, 54, 24, 14, 55, 25], [16, 45, 15, 14, 46, 16], [6, 147, 117, 4, 148, 118], [6, 73, 45, 14, 74, 46], [11, 54, 24, 16, 55, 25], [30, 46, 16, 2, 47, 17], [8, 132, 106, 4, 133, 107], [8, 75, 47, 13, 76, 48], [7, 54, 24, 22, 55, 25], [22, 45, 15, 13, 46, 16], [10, 142, 114, 2, 143, 115], [19, 74, 46, 4, 75, 47], [28, 50, 22, 6, 51, 23], [33, 46, 16, 4, 47, 17], [8, 152, 122, 4, 153, 123], [22, 73, 45, 3, 74, 46], [8, 53, 23, 26, 54, 24], [12, 45, 15, 28, 46, 16], [3, 147, 117, 10, 148, 118], [3, 73, 45, 23, 74, 46], [4, 54, 24, 31, 55, 25], [11, 45, 15, 31, 46, 16], [7, 146, 116, 7, 147, 117], [21, 73, 45, 7, 74, 46], [1, 53, 23, 37, 54, 24], [19, 45, 15, 26, 46, 16], [5, 145, 115, 10, 146, 116], [19, 75, 47, 10, 76, 48], [15, 54, 24, 25, 55, 25], [23, 45, 15, 25, 46, 16], [13, 145, 115, 3, 146, 116], [2, 74, 46, 29, 75, 47], [42, 54, 24, 1, 55, 25], [23, 45, 15, 28, 46, 16], [17, 145, 115], [10, 74, 46, 23, 75, 47], [10, 54, 24, 35, 55, 25], [19, 45, 15, 35, 46, 16], [17, 145, 115, 1, 146, 116], [14, 74, 46, 21, 75, 47], [29, 54, 24, 19, 55, 25], [11, 45, 15, 46, 46, 16], [13, 145, 115, 6, 146, 116], [14, 74, 46, 23, 75, 47], [44, 54, 24, 7, 55, 25], [59, 46, 16, 1, 47, 17], [12, 151, 121, 7, 152, 122], [12, 75, 47, 26, 76, 48], [39, 54, 24, 14, 55, 25], [22, 45, 15, 41, 46, 16], [6, 151, 121, 14, 152, 122], [6, 75, 47, 34, 76, 48], [46, 54, 24, 10, 55, 25], [2, 45, 15, 64, 46, 16], [17, 152, 122, 4, 153, 123], [29, 74, 46, 14, 75, 47], [49, 54, 24, 10, 55, 25], [24, 45, 15, 46, 46, 16], [4, 152, 122, 18, 153, 123], [13, 74, 46, 32, 75, 47], [48, 54, 24, 14, 55, 25], [42, 45, 15, 32, 46, 16], [20, 147, 117, 4, 148, 118], [40, 75, 47, 7, 76, 48], [43, 54, 24, 22, 55, 25], [10, 45, 15, 67, 46, 16], [19, 148, 118, 6, 149, 119], [18, 75, 47, 31, 76, 48], [34, 54, 24, 34, 55, 25], [20, 45, 15, 61, 46, 16]], i.getRSBlocks = function (t, _) {
                var e = i.getRsBlockTable(t, _);
                if (null == e) throw Error("bad rs block @ typeNumber:" + t + "/errorCorrectLevel:" + _);
                for (var r = e.length / 3, o = [], n = 0; n < r; n++) for (var s = e[3 * n + 0], u = e[3 * n + 1], a = e[3 * n + 2], h = 0; h < s; h++) o.push(new i(u, a));
                return o
            }, i.getRsBlockTable = function (t, _) {
                switch (_) {
                    case 1:
                        return i.RS_BLOCK_TABLE[4 * (t - 1) + 0];
                    case 0:
                        return i.RS_BLOCK_TABLE[4 * (t - 1) + 1];
                    case 3:
                        return i.RS_BLOCK_TABLE[4 * (t - 1) + 2];
                    case 2:
                        return i.RS_BLOCK_TABLE[4 * (t - 1) + 3]
                }
            }, s.prototype = {
                get: function (t) {
                    return 1 == (this.buffer[Math.floor(t / 8)] >>> 7 - t % 8 & 1)
                }, put: function (t, _) {
                    for (var e = 0; e < _; e++) this.putBit(1 == (t >>> _ - e - 1 & 1))
                }, getLengthInBits: function () {
                    return this.length
                }, putBit: function (t) {
                    var _ = Math.floor(this.length / 8);
                    this.buffer.length <= _ && this.buffer.push(0), t && (this.buffer[_] |= 128 >>> this.length % 8), this.length++
                }
            }, "string" == typeof _ && (_ = {text: _}), _ = t.extend({}, {
                render: "canvas",
                width: 256,
                height: 256,
                typeNumber: -1,
                correctLevel: 2,
                background: "#ffffff",
                foreground: "#000000"
            }, _), this.each(function () {
                var e;
                if ("canvas" == _.render) {
                    (e = new o(_.typeNumber, _.correctLevel)).addData(_.text), e.make();
                    var r = document.createElement("canvas");
                    r.width = _.width, r.height = _.height;
                    for (var n = r.getContext("2d"), i = _.width / e.getModuleCount(), s = _.height / e.getModuleCount(), u = 0; u < e.getModuleCount(); u++) for (var a = 0; a < e.getModuleCount(); a++) {
                        n.fillStyle = e.isDark(u, a) ? _.foreground : _.background;
                        var h = Math.ceil((a + 1) * i) - Math.floor(a * i),
                            l = Math.ceil((u + 1) * i) - Math.floor(u * i);
                        n.fillRect(Math.round(a * i), Math.round(u * s), h, l)
                    }
                } else for ((e = new o(_.typeNumber, _.correctLevel)).addData(_.text), e.make(), r = t("<table></table>").css("width", _.width + "px").css("height", _.height + "px").css("border", "0px").css("border-collapse", "collapse").css("background-color", _.background), n = _.width / e.getModuleCount(), i = _.height / e.getModuleCount(), s = 0; s < e.getModuleCount(); s++) for (u = t("<tr></tr>").css("height", i + "px").appendTo(r), a = 0; a < e.getModuleCount(); a++) t("<td></td>").css("width", n + "px").css("background-color", e.isDark(s, a) ? _.foreground : _.background).appendTo(u);
                $(e = r).appendTo(this)
            })
        }
    }($);
    const toUtf8 = (str) => {
        let out = '', i, len = str.length, c;
        for (i = 0; i < len; i++) {
            c = str.charCodeAt(i);
            if ((c >= 0x0001) && (c <= 0x007F)) {
                out += str.charAt(i)
            } else if (c > 0x07FF) {
                out += String.fromCharCode(0xE0 | ((c >> 12) & 0x0F));
                out += String.fromCharCode(0x80 | ((c >> 6) & 0x3F));
                out += String.fromCharCode(0x80 | ((c >> 0) & 0x3F))
            } else {
                out += String.fromCharCode(0xC0 | ((c >> 6) & 0x1F));
                out += String.fromCharCode(0x80 | ((c >> 0) & 0x3F))
            }
        }
        return out
    };
    $(elem).qrcode({
        render: (render || "canvas"),
        width: (width || 200),
        height: (height || 200),
        background: (background || "#FFFFFF"),
        foreground: (foreground || "#000000"),
        correctLevel: (correctLevel || 0),
        text: (utf8 === false ? text : toUtf8(text))
    })
};