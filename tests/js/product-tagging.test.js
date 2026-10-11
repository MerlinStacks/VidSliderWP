const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const jquery = require('jquery');

async function fixture(t) {
    const dom = new JSDOM(`<button class="reel-it-tag-product-btn" data-video-id="1"></button>
        <button class="reel-it-tag-product-btn" data-video-id="2"></button>
        <button class="reel-it-tag-product-btn" data-video-id="3"></button>
        <div id="reel-it-product-modal"><input id="reel-it-product-search">
            <div id="reel-it-product-results"></div><div id="reel-it-tagged-list"></div>
            <div id="reel-it-no-tags"></div><button id="reel-it-save-tags"></button>
            <button class="reel-it-product-modal-close-btn"></button></div>`, { runScripts: 'outside-only' });
    t.after(() => dom.window.close());
    const { window } = dom;
    const $ = jquery(window);
    const requests = [];
    window.jQuery = $;
    window.reelItSettings = { ajaxUrl: '/ajax', nonce: 'test', strings: {} };
    $.post = (url, data, callback) => {
        const deferred = $.Deferred();
        if (callback) deferred.done(callback);
        requests.push({ data, resolve: value => deferred.resolve(value), reject: () => deferred.reject() });
        return deferred.promise();
    };
    for (const file of ['reel-it-utils.js', 'reel-it-settings.js']) {
        window.eval(fs.readFileSync(path.join(__dirname, '../../admin/js', file), 'utf8'));
    }
    await new Promise(resolve => $(resolve));
    return {
        $, requests,
        open(id) { $(`.reel-it-tag-product-btn[data-video-id="${id}"]`).trigger('click'); },
        save() { $('#reel-it-save-tags').trigger('click'); },
        loaded(request, id) { request.resolve({ success: true, data: { products: [{ id, text: 'Product', image: '' }] } }); }
    };
}

test('saving while another video loads cannot copy the previous tags', async t => {
    const f = await fixture(t);
    f.open(1);
    f.loaded(f.requests[0], 101);
    f.open(2);
    assert.equal(f.$('#reel-it-save-tags').prop('disabled'), true);
    f.save();
    assert.equal(f.requests.length, 2);
    f.loaded(f.requests[1], 202);
    f.save();
    assert.equal(f.requests[2].data.video_id, 2);
    assert.deepEqual(Array.from(f.requests[2].data.products), [202]);
});

test('late responses from another modal selection cannot overwrite current tags', async t => {
    const f = await fixture(t);
    f.open(1);
    f.open(2);
    f.loaded(f.requests[1], 202);
    f.loaded(f.requests[0], 101);
    f.save();
    assert.equal(f.requests[2].data.video_id, 2);
    assert.deepEqual(Array.from(f.requests[2].data.products), [202]);
});

test('failed tag loading cannot erase existing tags by saving an empty selection', async t => {
    const f = await fixture(t);
    f.open(1);
    f.requests[0].reject();
    f.save();
    assert.equal(f.requests.length, 1);
    assert.equal(f.$('#reel-it-save-tags').prop('disabled'), true);
});

test('an older save completion does not re-enable saving during a new load', async t => {
    const f = await fixture(t);
    f.open(1);
    f.loaded(f.requests[0], 101);
    f.save();
    f.open(2);
    f.requests[1].resolve({ success: true });
    assert.equal(f.$('#reel-it-save-tags').prop('disabled'), true);
    f.save();
    assert.equal(f.requests.length, 3);
});
