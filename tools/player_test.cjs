const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');

function makeElement(tag = 'div') {
    const el = {
        tagName: tag.toUpperCase(),
        children: [],
        handlers: {},
        hidden: false,
        disabled: false,
        checked: false,
        textContent: '',
        className: '',
        value: '',
        type: '',
        name: '',
        dataset: {},
        attrs: {},
        classList: {
            set: new Set(),
            toggle(cls, force) { force ? this.set.add(cls) : this.set.delete(cls); },
            contains(cls) { return this.set.has(cls); },
        },
        setAttribute(k, v) { this.attrs[k] = v; },
        addEventListener(event, fn) { this.handlers[event] = fn; },
        focus() {},
        append(...items) { this.children.push(...items); },
        replaceChildren(...items) { this.children = [...items]; },
        querySelectorAll(sel) {
            const out = [];
            const walk = node => {
                if (!node || !node.children) { return; }
                for (const c of node.children) {
                    if (sel.includes('input') && c.tagName === 'INPUT') {
                        if (!sel.includes(':checked') || c.checked) { out.push(c); }
                    }
                    if (sel.includes('button') && c.tagName === 'BUTTON') { out.push(c); }
                    walk(c);
                }
            };
            walk(this);
            return out;
        },
        querySelector(sel) { return this.querySelectorAll(sel)[0] || null; },
    };
    return el;
}

async function scenario(failProgress = false) {
    const nodes = {};
    function node(id) {
        if (!nodes[id]) {
            nodes[id] = makeElement('div');
            nodes[id].hidden = true;
        }
        return nodes[id];
    }
    const video = node('videooynatici');
    Object.assign(video, {
        duration: 10, currentTime: 0, readyState: 1, paused: false,
        seeking: false, ended: false, playbackRate: 1,
        pause() { this.paused = true; },
        play() { this.paused = false; return Promise.resolve(); },
    });
    const requests = [];
    let clock = 0, module, savedProgress;
    const sandbox = {
        define(deps, factory) { module = factory({get_strings: async keys => keys.map(k => k.key)}); },
        document: {
            getElementById: node,
            createElement: makeElement,
            createTextNode: text => ({textContent: text}),
            querySelectorAll: () => [],
            addEventListener() {},
        },
        window: {addEventListener() {}},
        performance: {now: () => clock},
        setTimeout: fn => { fn(); return 1; },
        clearTimeout() {},
        M: {cfg: {sesskey: 'test', wwwroot: 'http://test.invalid/subdir'}},
        URLSearchParams, Date,
        fetch: async (url, options) => {
            assert(url.startsWith('http://test.invalid/subdir/mod/videoplayeray/'));
            assert.equal(options.body.get('sesskey'), 'test');
            const script = url.split('/').pop();
            requests.push(script);
            if (script === 'videoizlenmeisle.php') {
                if (failProgress) { throw new Error('offline'); }
                savedProgress = JSON.parse(options.body.get('progress'));
            }
            return {ok: true, json: async () => script === 'videoizlenmeal.php' ?
                {veri: '[]', position: 0, answered: []} : script === 'videocomplete.php' ?
                ['tam_izlenme', 100, 1] : {ok: true}};
        }
    };
    vm.runInNewContext(fs.readFileSync('videoplayeray/amd/src/player.js', 'utf8'), sandbox);
    await module.init({cmid: 42, track: true, questions: []});
    await new Promise(resolve => setImmediate(resolve));
    video.handlers.play();
    // Seek to the end: skipped seconds must remain unwatched.
    video.handlers.seeking();
    video.currentTime = 9;
    clock = 1000;
    video.handlers.timeupdate();
    assert.equal(nodes['videoplayeray-percent'].textContent, '0%');
    // Actual playback from the beginning.
    video.handlers.seeking();
    video.currentTime = 0;
    video.handlers.play();
    for (let second = 1; second <= 10; second++) {
        clock += 1000;
        video.currentTime = second;
        video.handlers.timeupdate();
    }
    assert.equal(nodes['videoplayeray-percent'].textContent, '100%');
    video.ended = true;
    video.paused = true;
    await video.handlers.ended();
    if (failProgress) {
        assert(!requests.includes('videocomplete.php'), 'failed final save must block completion');
        assert.equal(nodes['videoplayeray-status'].textContent, 'trackingfailed');
    } else {
        assert.equal(savedProgress.length, 10);
        assert(savedProgress.every(value => value === 1));
        assert(requests.lastIndexOf('videoizlenmeisle.php') < requests.indexOf('videocomplete.php'));
        assert.equal(nodes['videoplayeray-views'].textContent, 1);
    }
}

async function questionScenario() {
    const nodes = {};
    function node(id) {
        if (!nodes[id]) {
            nodes[id] = makeElement('div');
            nodes[id].hidden = true;
        }
        return nodes[id];
    }
    const video = node('videooynatici');
    Object.assign(video, {
        duration: 20, currentTime: 0, readyState: 1, paused: false,
        seeking: false, ended: false, playbackRate: 1,
        pause() { this.paused = true; },
        play() { this.paused = false; return Promise.resolve(); },
    });
    const events = [];
    let clock = 0, module;
    const sandbox = {
        define(deps, factory) { module = factory({get_strings: async keys => keys.map(k => k.key)}); },
        document: {
            getElementById: node,
            createElement: makeElement,
            createTextNode: text => ({textContent: text}),
            querySelectorAll: () => [],
            addEventListener() {},
        },
        window: {addEventListener() {}},
        performance: {now: () => clock},
        setTimeout: fn => { fn(); return 1; },
        clearTimeout() {},
        M: {cfg: {sesskey: 'test', wwwroot: 'http://test.invalid'}},
        URLSearchParams, Date,
        fetch: async (url, options) => {
            const script = url.split('/').pop();
            if (script === 'videocevapisle.php') {
                events.push(Object.fromEntries(options.body.entries()));
            }
            return {ok: true, json: async () => script === 'videoizlenmeal.php' ?
                {veri: '[]', position: 0, answered: []} : {ok: true}};
        }
    };
    vm.runInNewContext(fs.readFileSync('videoplayeray/amd/src/player.js', 'utf8'), sandbox);
    await module.init({
        cmid: 99,
        track: true,
        questions: [{
            id: 7,
            time: 5,
            type: 'multichoice',
            text: '2+2?',
            options: [{text: '3', correct: false}, {text: '4', correct: true}],
            rewind: 2,
            required: true,
            shuffle: false,
            feedbackCorrect: '',
            feedbackWrong: 'Try again',
        }],
    });
    await new Promise(resolve => setImmediate(resolve));

    // Attempt to seek past required question at 5s -> must snap back to 5s and open panel.
    video.currentTime = 12;
    video.handlers.seeking();
    assert.equal(video.currentTime, 5, 'seeking past required question must snap back');
    assert.equal(nodes['sorualani'].hidden, false, 'question panel must open');
    assert.equal(video.paused, true);

    // Submit wrong answer -> must show rewind button and seek to 2s on click.
    const radios1 = nodes['sorualani'].querySelectorAll('input');
    radios1[0].checked = true;
    const submit1 = nodes['sorualani'].querySelectorAll('button')[0];
    submit1.handlers.click();
    assert.equal(nodes['sorualani'].hidden, false, 'wrong answer keeps panel open for feedback');
    const rewindBtn = nodes['sorualani'].querySelectorAll('button')[1];
    rewindBtn.handlers.click();
    assert.equal(video.currentTime, 2, 'wrong answer rewinds to configured timestamp');
    assert.equal(nodes['sorualani'].hidden, true);

    // Reach 5s again and answer correctly.
    video.currentTime = 5;
    clock += 1000;
    video.handlers.timeupdate();
    assert.equal(nodes['sorualani'].hidden, false);
    const radios2 = nodes['sorualani'].querySelectorAll('input');
    radios2[1].checked = true;
    const submit2 = nodes['sorualani'].querySelectorAll('button')[0];
    submit2.handlers.click();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(nodes['sorualani'].hidden, true);
    assert.equal(video.currentTime, 6, 'correct answer advances past question');
    assert.equal(nodes['videoplayeray-qprogress'].textContent, '1 / 1');

    // Rewinding and passing 5s again must NOT re-open the already answered question.
    video.currentTime = 4;
    clock += 1000;
    video.handlers.timeupdate();
    video.currentTime = 5;
    clock += 1000;
    video.handlers.timeupdate();
    assert.equal(nodes['sorualani'].hidden, true, 'answered question must not re-open');
    assert(events.some(e => e.tur === 'cevapverildi' && e.questionid === '7' && e.durum === '1'));
}

(async () => {
    await scenario();
    await scenario(true);
    await questionScenario();
    console.log('PASS: player seeking, skip protection, wrong/correct feedback, answered memory, subdirectory URLs, CSRF token, final-save ordering, offline completion protection');
})().catch(error => { console.error(error); process.exitCode = 1; });
