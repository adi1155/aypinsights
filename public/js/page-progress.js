/**
 * AYP Insights — centered page load overlay (destination page only).
 */
(function () {
    'use strict';

    const DEFAULT_STEPS = [
        'Initializing dashboard',
        'Connecting to ERPNext',
        'Loading financial data',
        'Preparing charts & tables',
        'Finalizing view',
    ];

    const state = {
        progress: 0,
        stepIndex: 0,
        timer: null,
        complete: false,
        startedAt: window.__PAGE_PROGRESS_START || Date.now(),
        finishTimer: null,
        booted: false,
    };

    function getSteps() {
        try {
            const raw = document.body?.dataset?.progressSteps;
            if (raw) {
                return JSON.parse(raw);
            }
        } catch (e) { /* ignore */ }
        return DEFAULT_STEPS;
    }

    function overlayMarkup(steps) {
        const step = steps[0] || DEFAULT_STEPS[0];
        return `
            <div class="ayp-pp-backdrop" aria-hidden="true"></div>
            <div class="ayp-pp-card" role="presentation">
                <div class="ayp-pp-spinner" aria-hidden="true"></div>
                <p class="ayp-pp-pct" id="ayp-pp-pct">0%</p>
                <div class="ayp-pp-track">
                    <div class="ayp-pp-bar" id="ayp-pp-bar"></div>
                </div>
                <p class="ayp-pp-step" id="ayp-pp-step">${step}</p>
                <p class="ayp-pp-hint" id="ayp-pp-hint"></p>
            </div>
        `;
    }

    function createBar() {
        let wrap = document.getElementById('ayp-page-progress');
        const steps = getSteps();

        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'ayp-page-progress';
            wrap.setAttribute('role', 'progressbar');
            wrap.setAttribute('aria-valuemin', '0');
            wrap.setAttribute('aria-valuemax', '100');
            wrap.setAttribute('aria-busy', 'true');
            wrap.innerHTML = overlayMarkup(steps);
            (document.body || document.documentElement).appendChild(wrap);
        }

        document.documentElement.classList.add('ayp-loading');
        if (document.body) {
            document.body.classList.add('ayp-loading');
            document.body.classList.remove('ayp-loaded');
        }

        return wrap;
    }

    function setProgress(value, stepText) {
        state.progress = Math.min(100, Math.max(0, value));
        const bar = document.getElementById('ayp-pp-bar');
        const pct = document.getElementById('ayp-pp-pct');
        const step = document.getElementById('ayp-pp-step');
        const wrap = document.getElementById('ayp-page-progress');

        if (bar) {
            bar.style.width = state.progress + '%';
        }
        if (pct) {
            pct.textContent = Math.round(state.progress) + '%';
        }
        if (step && stepText) {
            step.textContent = stepText;
        }
        if (wrap) {
            wrap.setAttribute('aria-valuenow', String(Math.round(state.progress)));
        }
    }

    function setHint(text) {
        const hint = document.getElementById('ayp-pp-hint');
        if (hint) {
            hint.textContent = text || '';
        }
    }

    function stepForProgress(pct, steps) {
        const idx = Math.min(steps.length - 1, Math.floor((pct / 100) * steps.length));
        return steps[idx];
    }

    function scheduleFinish(delayMs) {
        clearTimeout(state.finishTimer);
        state.finishTimer = setTimeout(finish, delayMs);
    }

    function startSimulation() {
        const steps = getSteps();
        createBar();
        setProgress(5, steps[0]);
        setHint('');

        clearInterval(state.timer);
        state.timer = setInterval(function () {
            if (state.complete) {
                return;
            }

            const elapsed = Date.now() - state.startedAt;
            let cap = 94;

            if (document.readyState === 'interactive' || document.readyState === 'complete') {
                cap = 97;
            }

            if (elapsed > 25000) {
                setHint('Loading live data from ERPNext — first load can take up to a minute.');
                cap = 98;
            }

            const increment = elapsed > 30000 ? 1.2 : elapsed > 15000 ? 2 : 3.5;
            const next = Math.min(cap, state.progress + increment);
            state.stepIndex = Math.min(steps.length - 1, Math.floor((next / 100) * steps.length));
            setProgress(next, steps[state.stepIndex]);
        }, 300);
    }

    /** Remove overlay immediately when leaving this page (no progress on source during navigation). */
    function teardown() {
        state.complete = true;
        clearInterval(state.timer);
        clearTimeout(state.finishTimer);
        state.timer = null;
        state.finishTimer = null;

        const wrap = document.getElementById('ayp-page-progress');
        if (wrap) {
            wrap.remove();
        }

        document.documentElement.classList.remove('ayp-loading');
        if (document.body) {
            document.body.classList.remove('ayp-loading', 'ayp-loaded');
        }
    }

    function finish() {
        if (state.complete) {
            return;
        }
        state.complete = true;
        clearInterval(state.timer);
        clearTimeout(state.finishTimer);

        const steps = getSteps();
        setProgress(100, steps[steps.length - 1] || 'Complete');
        setHint('');

        const wrap = document.getElementById('ayp-page-progress');
        if (wrap) {
            wrap.setAttribute('aria-busy', 'false');
            wrap.classList.add('ayp-pp-done');
            setTimeout(function () {
                wrap.classList.add('ayp-pp-hide');
                document.documentElement.classList.remove('ayp-loading');
                if (document.body) {
                    document.body.classList.remove('ayp-loading');
                    document.body.classList.add('ayp-loaded');
                }
                setTimeout(function () {
                    wrap.remove();
                }, 450);
            }, 350);
        } else {
            document.documentElement.classList.remove('ayp-loading');
            if (document.body) {
                document.body.classList.remove('ayp-loading');
                document.body.classList.add('ayp-loaded');
            }
        }
    }

    function boot() {
        if (state.booted) {
            return;
        }
        state.booted = true;
        state.complete = false;
        state.progress = 0;
        state.startedAt = window.__PAGE_PROGRESS_START || Date.now();
        window.__dashboardReadyFired = false;

        startSimulation();

        document.addEventListener('DOMContentLoaded', function onDomReady() {
            const steps = getSteps();
            setProgress(Math.max(state.progress, 78), stepForProgress(78, steps));

            if (window.__DASHBOARD_AWAITING_CHARTS) {
                scheduleFinish(6000);
            } else {
                scheduleFinish(400);
            }
        });

        window.addEventListener('load', function onWindowLoad() {
            if (!window.__DASHBOARD_AWAITING_CHARTS) {
                scheduleFinish(200);
            } else {
                scheduleFinish(5000);
            }
        });

        window.addEventListener('dashboard:ready', function onDashboardReady() {
            setProgress(100, 'Complete');
            scheduleFinish(150);
        });
    }

    window.addEventListener('pagehide', teardown);
    window.addEventListener('beforeunload', teardown);

    if (document.body) {
        boot();
    } else {
        document.addEventListener('DOMContentLoaded', boot);
    }

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            teardown();
            state.booted = false;
            state.complete = false;
            window.__PAGE_PROGRESS_START = Date.now();
            boot();
        }
    });

    window.AypPageProgress = { finish: finish, setProgress: setProgress, teardown: teardown };
})();
