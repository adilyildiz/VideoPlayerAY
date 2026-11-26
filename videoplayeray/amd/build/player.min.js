/**
 * Native video player with progress tracking, timed questions, and skip protection.
 * @module mod_videoplayeray/player
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str'], function(Str) {
    'use strict';
    const shuffle = list => {
        const copy = list.slice();
        for (let i = copy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [copy[i], copy[j]] = [copy[j], copy[i]];
        }
        return copy;
    };
    const normalizeQuestions = configQuestions => {
        if (Array.isArray(configQuestions) && configQuestions.length) {
            return configQuestions.map(q => ({
                id: Number(q.id) || 0,
                key: Number(q.id) > 0 ? String(q.id) : 't' + Math.floor(Number(q.time) || 0),
                time: Math.max(0, Math.floor(Number(q.time) || 0)),
                type: q.type || 'multichoice',
                text: String(q.text || ''),
                options: Array.isArray(q.options) ? q.options.map(o => ({text: String(o.text || ''), correct: o.correct === true})) : [],
                rewind: Math.max(0, Math.floor(Number(q.rewind) || 0)),
                required: q.required !== false,
                shuffle: q.shuffle === true,
                feedbackCorrect: String(q.feedbackCorrect || ''),
                feedbackWrong: String(q.feedbackWrong || ''),
            })).filter(q => q.options.length >= 2).sort((a, b) => a.time - b.time);
        }
        const legacy = (configQuestions && !Array.isArray(configQuestions) && Object.keys(configQuestions).length)
            ? configQuestions : (typeof window !== 'undefined' && window.olaylar ? window.olaylar : {});
        return Object.keys(legacy).map(sec => {
            const item = legacy[sec];
            const time = Math.floor(Number(sec));
            if (!Number.isFinite(time) || !Array.isArray(item) || !Array.isArray(item[1])) { return null; }
            return {
                id: 0,
                key: 't' + Math.max(0, time),
                time: Math.max(0, time),
                type: 'multichoice',
                text: String(item[0] || ''),
                options: item[1].map(o => ({text: String(o[0] || ''), correct: o[1] === true})),
                rewind: Math.max(0, Math.floor(Number(item[2]) || 0)),
                required: true,
                shuffle: false,
                feedbackCorrect: '',
                feedbackWrong: '',
            };
        }).filter(Boolean).sort((a, b) => a.time - b.time);
    };

    return {init: async function(config) {
        const video = document.getElementById('videooynatici');
        if (!video) { return; }
        const keys = ['answer', 'selectanswer', 'correctanswer', 'wronganswer', 'trackingfailed',
            'completedmessage', 'incompletemessage', 'missingquestionsmessage', 'cannotseekquestion',
            'continuevideo', 'rewindandwatch', 'saved', 'saving'];
        const values = await Str.get_strings(keys.map(key => ({key, component: 'mod_videoplayeray'})));
        const strings = Object.fromEntries(keys.map((key, index) => [key, values[index]]));
        const panel = document.getElementById('sorualani');
        const status = document.getElementById('videoplayeray-status');
        const toast = document.getElementById('videoplayeray-toast');
        const bar = document.getElementById('videoplayeray-progress');
        const percentText = document.getElementById('videoplayeray-percent');
        const saved = document.getElementById('videoplayeray-saved');
        const qCountEl = document.getElementById('videoplayeray-qprogress');

        let progress = [], ready = false, loading = false, lastTime = null, lastWall = null;
        let activeQuestion = null, previousFocus = null, completed = false, finishing = false;
        let lastSave = 0, queue = Promise.resolve(), toastTimer = null, internalSeek = false;
        const answered = new Set();
        const session = new Date().toISOString();
        const getQuestions = () => normalizeQuestions(config.questions);

        const showToast = (message, kind = 'info') => {
            if (status) { status.textContent = message; status.hidden = false; }
            if (toast) {
                toast.textContent = message;
                toast.className = 'videoplayeray-toast videoplayeray-toast-' + kind;
                toast.hidden = false;
                if (toastTimer) { clearTimeout(toastTimer); }
                toastTimer = setTimeout(() => { toast.hidden = true; }, 4500);
            }
        };
        const show = message => showToast(message, 'info');

        const refreshQuestionsUI = () => {
            const list = getQuestions();
            if (qCountEl) {
                const done = list.filter(q => answered.has(q.key)).length;
                qCountEl.textContent = `${done} / ${list.length}`;
            }
            if (document.querySelectorAll) {
                document.querySelectorAll('[data-qkey]').forEach(el => {
                    const solved = answered.has(el.dataset.qkey);
                    el.classList.toggle('is-answered', solved);
                });
            }
        };
        const refresh = () => {
            const percent = progress.length ? Math.round(100 * progress.reduce((a, b) => a + b, 0) / progress.length) : 0;
            bar.value = percent;
            percentText.textContent = percent + '%';
        };
        const request = async (script, params) => {
            const body = new URLSearchParams(Object.assign({sesskey: M.cfg.sesskey}, params));
            const response = await fetch(M.cfg.wwwroot + '/mod/videoplayeray/' + script + '.php', {
                method: 'POST', credentials: 'same-origin', body,
                keepalive: body.toString().length < 60000
            });
            if (!response.ok) { throw new Error(strings.trackingfailed); }
            const data = await response.json();
            if (data.error || data.exception) { throw new Error(data.error || data.message); }
            return data;
        };
        const enqueue = work => {
            const task = queue.then(work);
            queue = task.catch(() => { saved.textContent = strings.trackingfailed; showToast(strings.trackingfailed, 'danger'); });
            return task;
        };
        const log = (tur, extra) => {
            if (config.track) {
                enqueue(() => request('videocevapisle', Object.assign({videoid: config.cmid, tur,
                    oturum: session, baslama: video.currentTime}, extra))).catch(() => {});
            }
        };
        const save = () => {
            if (!config.track || !ready || !progress.length || completed || finishing) { return Promise.resolve(); }
            lastSave = Date.now();
            const snapshot = JSON.stringify(progress);
            const position = video.currentTime;
            saved.textContent = strings.saving;
            return enqueue(async () => {
                await request('videoizlenmeisle', {videoid: config.cmid, progress: snapshot, position});
                saved.textContent = strings.saved;
            });
        };
        const seekTo = target => {
            internalSeek = true;
            video.currentTime = Math.max(0, Math.min(target, Number.isFinite(video.duration) ? video.duration : target));
            lastTime = null;
            setTimeout(() => { internalSeek = false; }, 0);
        };
        const findBlockingQuestion = targetTime => {
            return getQuestions().find(q => q.required && !answered.has(q.key) && targetTime > q.time + 0.5) || null;
        };

        const initialise = async () => {
            const length = Math.ceil(video.duration);
            if (!Number.isFinite(length) || length < 1 || length > 86400 || ready || loading) { return; }
            loading = true;
            progress = Array(length).fill(0);
            if (config.track) {
                try {
                    const data = await request('videoizlenmeal', {videoid: config.cmid});
                    const stored = JSON.parse(data.veri);
                    if (Array.isArray(stored)) {
                        progress = progress.map((_, index) => stored[index] === 1 || stored[index] === '1' ? 1 : 0);
                    }
                    if (Array.isArray(data.answered)) {
                        data.answered.forEach(k => answered.add(String(k)));
                    }
                    const resume = document.getElementById('videoplayeray-resume');
                    if (resume && data.position > 1 && data.position < video.duration - 1) {
                        resume.hidden = false;
                        resume.addEventListener('click', () => {
                            const blocker = findBlockingQuestion(data.position);
                            seekTo(blocker ? blocker.time : data.position);
                            resume.hidden = true;
                            if (blocker) { ask(blocker); } else { video.play().catch(() => {}); }
                        }, {once: true});
                    }
                } catch (error) {
                    showToast(strings.trackingfailed, 'danger');
                    loading = false;
                    return;
                }
            }
            ready = true; loading = false; refresh(); refreshQuestionsUI();
        };

        const closeQuestion = () => {
            panel.hidden = true;
            activeQuestion = null;
            lastTime = null;
            if (previousFocus && previousFocus.focus) { previousFocus.focus(); }
        };

        const ask = question => {
            if (!question || !Array.isArray(question.options) || question.options.length < 2) { return; }
            activeQuestion = question;
            video.pause();
            if (document.fullscreenElement && document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
            }
            if (video.webkitExitFullscreen) { video.webkitExitFullscreen(); }
            previousFocus = document.activeElement;
            panel.replaceChildren();

            const title = document.createElement('h3');
            title.textContent = question.text;
            panel.append(title);

            const feedbackBox = document.createElement('div');
            feedbackBox.className = 'videoplayeray-qfeedback';
            feedbackBox.hidden = true;
            feedbackBox.setAttribute('role', 'alert');

            const optionsList = question.shuffle ? shuffle(question.options) : question.options;
            const formWrap = document.createElement('div');
            formWrap.className = 'videoplayeray-qoptions';
            optionsList.forEach((opt, index) => {
                const label = document.createElement('label');
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'videoplayeray-answer';
                radio.value = String(index);
                label.append(radio, document.createTextNode(' ' + opt.text));
                formWrap.append(label);
            });
            panel.append(formWrap, feedbackBox);

            const actions = document.createElement('div');
            actions.className = 'videoplayeray-qactions';
            const submitBtn = document.createElement('button');
            submitBtn.type = 'button';
            submitBtn.className = 'btn btn-primary';
            submitBtn.textContent = strings.answer;

            submitBtn.addEventListener('click', () => {
                const selected = formWrap.querySelector('input:checked');
                if (!selected) {
                    feedbackBox.textContent = strings.selectanswer;
                    feedbackBox.className = 'videoplayeray-qfeedback alert alert-warning';
                    feedbackBox.hidden = false;
                    showToast(strings.selectanswer, 'warning');
                    return;
                }
                const chosen = optionsList[Number(selected.value)];
                const isCorrect = chosen.correct === true;
                log('cevapverildi', {
                    questionid: question.id,
                    soru: question.text,
                    cevap: chosen.text,
                    durum: isCorrect ? 1 : 0,
                });
                formWrap.querySelectorAll('input').forEach(el => { el.disabled = true; });
                submitBtn.hidden = true;

                if (isCorrect) {
                    answered.add(question.key);
                    refreshQuestionsUI();
                    const msg = question.feedbackCorrect || strings.correctanswer;
                    if (question.feedbackCorrect) {
                        feedbackBox.textContent = msg;
                        feedbackBox.className = 'videoplayeray-qfeedback alert alert-success';
                        feedbackBox.hidden = false;
                        const contBtn = document.createElement('button');
                        contBtn.type = 'button';
                        contBtn.className = 'btn btn-success';
                        contBtn.textContent = strings.continuevideo;
                        contBtn.addEventListener('click', () => {
                            closeQuestion();
                            seekTo(Math.min(question.time + 1, video.duration));
                            showToast(strings.correctanswer, 'success');
                            video.play().catch(() => {});
                        });
                        actions.append(contBtn);
                        contBtn.focus();
                    } else {
                        closeQuestion();
                        seekTo(Math.min(question.time + 1, video.duration));
                        showToast(msg, 'success');
                        video.play().catch(() => {});
                    }
                } else {
                    const msg = question.feedbackWrong || strings.wronganswer;
                    feedbackBox.textContent = msg;
                    feedbackBox.className = 'videoplayeray-qfeedback alert alert-danger';
                    feedbackBox.hidden = false;
                    showToast(msg, 'danger');
                    const rewindBtn = document.createElement('button');
                    rewindBtn.type = 'button';
                    rewindBtn.className = 'btn btn-secondary';
                    rewindBtn.textContent = strings.rewindandwatch;
                    rewindBtn.addEventListener('click', () => {
                        closeQuestion();
                        seekTo(question.rewind);
                        video.play().catch(() => {});
                    });
                    actions.append(rewindBtn);
                    rewindBtn.focus();
                }
            });

            actions.append(submitBtn);
            panel.append(actions);
            panel.hidden = false;
            (formWrap.querySelector('input') || submitBtn).focus();
            log('sorusoruldu', {questionid: question.id, soru: question.text});
        };

        panel.addEventListener('keydown', event => {
            if (event.key !== 'Tab') { return; }
            const inputs = Array.from(panel.querySelectorAll('input:not(:disabled), button:not([hidden])'));
            if (!inputs.length) { return; }
            const first = inputs[0], last = inputs[inputs.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault(); last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault(); first.focus();
            }
        });

        video.addEventListener('loadedmetadata', initialise);
        if (video.readyState >= 1) { initialise(); }
        video.addEventListener('play', () => {
            if (activeQuestion !== null || finishing) { video.pause(); return; }
            if (completed) { progress.fill(0); answered.clear(); completed = false; refresh(); refreshQuestionsUI(); }
            if (!ready) { initialise(); }
            const blocker = findBlockingQuestion(video.currentTime);
            if (blocker) {
                seekTo(blocker.time);
                showToast(strings.cannotseekquestion, 'warning');
                ask(blocker);
                return;
            }
            lastTime = video.currentTime; lastWall = performance.now();
            log(video.currentTime < 1 ? 'izlemebasladi' : 'izlemedevamedildi');
        });
        video.addEventListener('timeupdate', () => {
            if (video.seeking || (video.paused && !video.ended) || !ready || finishing) { return; }
            const time = video.currentTime, wall = performance.now();
            const delta = lastTime === null ? 0 : time - lastTime;
            if (lastTime !== null && delta > 0 && delta <= Math.max(2, (wall - lastWall) / 1000 * video.playbackRate + .5)) {
                for (let second = Math.floor(lastTime); second < Math.ceil(time) && second < progress.length; second++) {
                    progress[second] = 1;
                }
            }
            const previous = lastTime;
            lastTime = time; lastWall = wall;
            refresh();
            if (activeQuestion === null) {
                const startSec = Math.floor(previous === null ? time : previous);
                const endSec = Math.floor(time);
                const due = getQuestions().find(q => !answered.has(q.key) && q.time <= endSec && q.time >= startSec);
                if (due) {
                    ask(due);
                } else {
                    const blocker = findBlockingQuestion(time);
                    if (blocker) {
                        seekTo(blocker.time);
                        showToast(strings.cannotseekquestion, 'warning');
                        ask(blocker);
                    }
                }
            }
            if (Date.now() - lastSave >= 5000) { save().catch(() => {}); }
        });
        video.addEventListener('seeking', () => {
            lastTime = null;
            if (internalSeek) { return; }
            if (activeQuestion !== null) {
                seekTo(activeQuestion.time);
                video.pause();
                return;
            }
            const blocker = findBlockingQuestion(video.currentTime);
            if (blocker) {
                seekTo(blocker.time);
                showToast(strings.cannotseekquestion, 'warning');
                ask(blocker);
            }
        });
        video.addEventListener('seeked', () => {
            if (!internalSeek) { log('izlemekaydirildi'); }
        });
        video.addEventListener('pause', () => { save().catch(() => {}); log('izlemebekletildi'); });
        video.addEventListener('volumechange', () => { log('seskes', {durum: video.muted ? 1 : 0}); });
        document.addEventListener('fullscreenchange', () => { log('tamekran', {durum: document.fullscreenElement ? 1 : 0}); });
        document.addEventListener('visibilitychange', () => { if (document.hidden) { save().catch(() => {}); } });
        window.addEventListener('pagehide', () => { save().catch(() => {}); });
        video.addEventListener('ended', async () => {
            if (!config.track || !ready || finishing || completed) { return; }
            const blocker = findBlockingQuestion(video.duration + 1);
            if (blocker) {
                seekTo(blocker.time);
                showToast(strings.cannotseekquestion, 'warning');
                ask(blocker);
                return;
            }
            try {
                const saving = save(); finishing = true;
                await saving;
                const result = await enqueue(() => request('videocomplete', {id: config.cmid}));
                if (result[0] === 'tam_izlenme') {
                    completed = true;
                    answered.clear();
                    refreshQuestionsUI();
                    document.getElementById('videoplayeray-views').textContent = result[2];
                    showToast(strings.completedmessage.replace('{percent}', result[1]).replace('{count}', result[2]), 'success');
                } else if (result[0] === 'eksik_soru') {
                    showToast(strings.missingquestionsmessage.replace('{missing}', result[3] || 1), 'warning');
                    const firstMissing = getQuestions().find(q => q.required && !answered.has(q.key));
                    if (firstMissing) { seekTo(firstMissing.time); ask(firstMissing); }
                } else {
                    showToast(strings.incompletemessage.replace('{percent}', result[1]).replace('{count}', result[2]), 'warning');
                }
                log('izlemebitti', {durum: completed ? 1 : 0});
            } catch (error) { showToast(strings.trackingfailed, 'danger'); }
            finally { finishing = false; }
        });
    }};
});
