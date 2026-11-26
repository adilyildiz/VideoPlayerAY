/**
 * Interactive video preview and timestamp picker for question management.
 * @module mod_videoplayeray/questions
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';
    const formatTime = seconds => {
        const total = Math.max(0, Math.floor(seconds));
        const h = Math.floor(total / 3600);
        const m = Math.floor((total % 3600) / 60);
        const s = total % 60;
        const pad = n => String(n).padStart(2, '0');
        return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
    };
    return {init: function(config) {
        const video = document.getElementById('videoplayeray-preview');
        const curtime = document.getElementById('videoplayeray-curtime');
        const duration = document.getElementById('videoplayeray-duration');
        const pickTime = document.getElementById('videoplayeray-picktime');
        const pickRewind = document.getElementById('videoplayeray-pickrewind');
        const timeline = document.getElementById('videoplayeray-timeline');
        const triggerInput = document.querySelector('input[name="timetrigger"]');
        const rewindInput = document.querySelector('input[name="rewindtime"]');
        if (!video) { return; }

        const updateTime = () => {
            if (curtime) { curtime.textContent = formatTime(video.currentTime || 0); }
        };
        const renderMarkers = () => {
            if (!Number.isFinite(video.duration) || video.duration <= 0) { return; }
            if (duration) { duration.textContent = formatTime(video.duration); }
            document.querySelectorAll('.videoplayeray-overflow-warn').forEach(badge => {
                const t = Number(badge.dataset.time);
                badge.hidden = !(Number.isFinite(t) && t > Math.floor(video.duration));
            });
            if (!timeline || !Array.isArray(config.questions) || !config.questions.length) { return; }
            timeline.replaceChildren();
            config.questions.forEach(q => {
                const pct = Math.min(100, Math.max(0, (q.time / video.duration) * 100));
                const marker = document.createElement('button');
                marker.type = 'button';
                marker.className = 'videoplayeray-marker';
                marker.style.left = pct + '%';
                marker.title = `${formatTime(q.time)} — ${q.text}`;
                marker.addEventListener('click', () => {
                    video.currentTime = Math.min(q.time, video.duration);
                    video.pause();
                    updateTime();
                });
                timeline.append(marker);
            });
            timeline.hidden = false;
        };

        video.addEventListener('timeupdate', updateTime);
        video.addEventListener('seeked', updateTime);
        video.addEventListener('loadedmetadata', renderMarkers);
        if (video.readyState >= 1) { renderMarkers(); }

        if (pickTime && triggerInput) {
            pickTime.addEventListener('click', () => {
                video.pause();
                triggerInput.value = formatTime(video.currentTime || 0);
                triggerInput.focus();
            });
        }
        if (pickRewind && rewindInput) {
            pickRewind.addEventListener('click', () => {
                rewindInput.value = formatTime(video.currentTime || 0);
                rewindInput.focus();
            });
        }
        document.querySelectorAll('.videoplayeray-seek').forEach(btn => {
            btn.addEventListener('click', () => {
                const t = Number(btn.dataset.time);
                if (Number.isFinite(t)) {
                    video.currentTime = Math.min(t, Number.isFinite(video.duration) ? video.duration : t);
                    video.pause();
                    updateTime();
                }
            });
        });
    }};
});
