const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');

function fixture(t, { autoplay = true, top = 100 } = {}) {
    const dom = new JSDOM(`<div class="reel-it-container" data-autoplay="${autoplay ? 1 : 0}" data-slider-speed="1000">
        <div class="reel-it-slider">${[1, 2].map(id => `<div class="reel-it-slide">
            <div class="reel-it-video-container" data-video-id="${id}" data-video-src="https://example.org/${id}.mp4">
                <div class="reel-it-video-overlay"><button class="reel-it-play-button">Play</button></div>
                <div class="reel-it-controls-container"></div>
            </div></div>`).join('')}</div></div>`, { runScripts: 'outside-only', pretendToBeVisual: true });
    t.after(() => dom.window.close());
    const { window } = dom;
    const observers = [];
    window.IntersectionObserver = class {
        constructor(callback) { this.callback = callback; observers.push(this); }
        observe() {}
        disconnect() { this.disconnected = true; }
    };
    const media = window.HTMLMediaElement.prototype;
    Object.defineProperty(media, 'paused', { get() { return this._paused !== false; } });
    media.play = function () { this._paused = false; this.dispatchEvent(new window.Event('play')); return Promise.resolve(); };
    media.pause = function () { this._paused = true; this.dispatchEvent(new window.Event('pause')); };
    media.load = function () {};
    const rect = (y, left = 0) => ({ top: y, bottom: y + 400, left, right: left + 200, width: 200, height: 400 });
    const containers = Array.from(window.document.querySelectorAll('.reel-it-video-container'));
    let y = top;
    containers.forEach(vc => { vc.getBoundingClientRect = () => rect(y); });
    const track = window.document.querySelector('.reel-it-slider');
    track.getBoundingClientRect = () => rect(y);
    track.scrollTo = () => {};
    let source = fs.readFileSync(path.join(__dirname, '../../public/js/reel-it-public.js'), 'utf8');
    // Exercise the real class independently of the page-discovery observer.
    source = source.slice(0, source.indexOf('    /* Why: module-scoped')) + 'window.TestSlider = VideoSlider; })();';
    window.eval(source);
    const slider = new window.TestSlider(window.document.querySelector('.reel-it-container'));
    t.after(() => slider.destroy());
    return { window, slider, containers, observers, move(yValue) { y = yValue; observers[0].callback(); } };
}

test('preload-margin initialization does not autoplay until actually visible', async t => {
    const f = fixture(t, { top: 900 });
    assert.equal(f.containers[0].querySelector('video'), null);
    f.move(100);
    await Promise.resolve();
    assert.ok(f.containers[0].querySelector('video'));
    assert.ok(f.containers[0].classList.contains('playing'));
});

test('scrolling out immediately stops even a freshly started video', async t => {
    const f = fixture(t);
    const video = f.containers[0].querySelector('video');
    assert.equal(video.currentTime, 0);
    f.move(900);
    await Promise.resolve();
    assert.equal(video.isConnected, false);
    assert.equal(video.paused, true);
    assert.equal(f.slider._boundHandlers.some(h => h.el === video), false);
});

test('repeated playback releases video listeners without removing navigation listeners', async t => {
    const f = fixture(t, { autoplay: false });
    const baseline = f.slider._boundHandlers.length;
    for (let i = 0; i < 50; i++) {
        f.containers[0].querySelector('button').click();
        await Promise.resolve();
        assert.ok(f.containers[0].querySelector('video'));
        f.slider.destroyVideo(f.containers[0]);
        assert.equal(f.slider._boundHandlers.length, baseline);
    }
});

test('leaving the viewport cancels queued auto-advance and does not restart on return', async t => {
    const f = fixture(t);
    await Promise.resolve();
    f.containers[0].querySelector('video').dispatchEvent(new f.window.Event('ended'));
    assert.ok(f.slider.autoAdvanceTimeout);
    f.move(900);
    assert.equal(f.slider.autoAdvanceTimeout, null);
    f.move(100);
    assert.equal(f.containers[0].querySelector('video'), null);
    assert.equal(f.containers[1].querySelector('video'), null);
});

test('hidden tabs stop playback and teardown disconnects visibility observers', async t => {
    const f = fixture(t);
    await Promise.resolve();
    Object.defineProperty(f.window.document, 'hidden', { value: true, configurable: true });
    f.window.document.dispatchEvent(new f.window.Event('visibilitychange'));
    assert.equal(f.containers[0].querySelector('video'), null);
    f.slider.destroy();
    assert.equal(f.observers[0].disconnected, true);
    assert.equal(f.slider._boundHandlers.length, 0);
});

test('horizontal scrolling cancels advancement even after the finished video was removed', async t => {
    const f = fixture(t);
    await Promise.resolve();
    f.containers[0].querySelector('video').dispatchEvent(new f.window.Event('ended'));
    assert.ok(f.slider.autoAdvanceTimeout);
    f.containers[0].getBoundingClientRect = () => ({ left: -400, right: -200, top: 100, bottom: 500, width: 200, height: 400 });
    f.observers[0].callback();
    assert.equal(f.slider.autoAdvanceTimeout, null);
});

test('auto-advance waits until a horizontally clipped target becomes visible', async t => {
    const f = fixture(t, { autoplay: false });
    f.containers[1].getBoundingClientRect = () => ({ left: 900, right: 1100, top: 100, bottom: 500, width: 200, height: 400 });
    f.slider.navToSlide(1, true);
    assert.equal(f.containers[1].querySelector('video'), null);
    f.containers[1].getBoundingClientRect = f.containers[0].getBoundingClientRect;
    f.observers[0].callback();
    await Promise.resolve();
    assert.ok(f.containers[1].querySelector('video'));
});
