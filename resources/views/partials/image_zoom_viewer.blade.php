{{-- Visionneuse d'images réutilisable : zoom (molette + boutons), rotation, glisser-déposer.
     Ajouté le 2026-08-21 pour permettre d'inspecter les pièces d'identité (recto/verso)
     en grand, zoomées et correctement orientées, depuis les pages transactions/customers.
     Usage : appeler docViewerOpen('URL_IMAGE', 'Titre optionnel') au clic sur une vignette. --}}
<div id="docViewerOverlay" class="doc-viewer-overlay" aria-hidden="true">
    <div class="doc-viewer-toolbar">
        <span id="docViewerTitle" class="doc-viewer-title"></span>
        <div class="doc-viewer-actions">
            <button type="button" class="doc-viewer-btn" title="Zoom arrière" onclick="docViewerZoom(-0.25)"><i class="mdi mdi-magnify-minus-outline"></i></button>
            <button type="button" class="doc-viewer-btn" title="Zoom avant" onclick="docViewerZoom(0.25)"><i class="mdi mdi-magnify-plus-outline"></i></button>
            <button type="button" class="doc-viewer-btn" title="Pivoter à gauche" onclick="docViewerRotate(-90)"><i class="mdi mdi-rotate-left"></i></button>
            <button type="button" class="doc-viewer-btn" title="Pivoter à droite" onclick="docViewerRotate(90)"><i class="mdi mdi-rotate-right"></i></button>
            <button type="button" class="doc-viewer-btn" title="Réinitialiser" onclick="docViewerReset()"><i class="mdi mdi-restore"></i></button>
            <button type="button" class="doc-viewer-btn doc-viewer-close" title="Fermer" onclick="docViewerClose()"><i class="mdi mdi-close"></i></button>
        </div>
    </div>
    <div class="doc-viewer-stage" id="docViewerStage">
        <img id="docViewerImg" src="" alt="" draggable="false">
    </div>
    <div class="doc-viewer-hint">Molette pour zoomer &middot; glisser pour déplacer &middot; Échap pour fermer</div>
</div>

<style>
    .doc-thumb-btn {
        display: inline-block;
        padding: 0;
        border: 1px solid rgba(0,0,0,.1);
        border-radius: 8px;
        overflow: hidden;
        background: #f4f4f4;
        cursor: zoom-in;
        position: relative;
        line-height: 0;
    }
    .doc-thumb-btn img {
        display: block;
        border-radius: 8px;
    }
    .doc-thumb-btn .doc-thumb-zoom-icon {
        position: absolute;
        right: 4px;
        bottom: 4px;
        background: rgba(0,0,0,.55);
        color: #fff;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        pointer-events: none;
    }

    .doc-viewer-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 20000;
        background: rgba(15, 15, 20, .92);
        flex-direction: column;
    }
    .doc-viewer-overlay.doc-viewer-open { display: flex; }
    .doc-viewer-toolbar {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        color: #fff;
        font-size: 14px;
    }
    .doc-viewer-title { opacity: .85; }
    .doc-viewer-actions { display: flex; gap: 6px; }
    .doc-viewer-btn {
        background: rgba(255,255,255,.12);
        border: none;
        color: #fff;
        width: 36px;
        height: 36px;
        border-radius: 6px;
        font-size: 18px;
        cursor: pointer;
    }
    .doc-viewer-btn:hover { background: rgba(255,255,255,.25); }
    .doc-viewer-close { background: rgba(220,53,69,.75); }
    .doc-viewer-close:hover { background: rgba(220,53,69,.95); }
    .doc-viewer-stage {
        flex: 1 1 auto;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: grab;
        touch-action: none;
    }
    .doc-viewer-stage.doc-viewer-dragging { cursor: grabbing; }
    #docViewerImg {
        max-width: 90vw;
        max-height: 80vh;
        user-select: none;
        will-change: transform;
        transition: transform .05s linear;
    }
    .doc-viewer-hint {
        flex: 0 0 auto;
        text-align: center;
        color: rgba(255,255,255,.6);
        font-size: 12px;
        padding: 6px 0 12px;
    }
</style>

<script>
    (function () {
        var state = { scale: 1, rotate: 0, x: 0, y: 0, dragging: false, startX: 0, startY: 0 };

        function applyTransform() {
            var img = document.getElementById('docViewerImg');
            if (!img) return;
            img.style.transform = 'translate(' + state.x + 'px,' + state.y + 'px) scale(' + state.scale + ') rotate(' + state.rotate + 'deg)';
        }

        window.docViewerOpen = function (src, title) {
            var overlay = document.getElementById('docViewerOverlay');
            var img = document.getElementById('docViewerImg');
            var titleEl = document.getElementById('docViewerTitle');
            if (!overlay || !img) return;
            state = { scale: 1, rotate: 0, x: 0, y: 0, dragging: false, startX: 0, startY: 0 };
            img.src = src;
            titleEl.textContent = title || '';
            applyTransform();
            overlay.classList.add('doc-viewer-open');
            overlay.setAttribute('aria-hidden', 'false');
        };

        window.docViewerClose = function () {
            var overlay = document.getElementById('docViewerOverlay');
            if (!overlay) return;
            overlay.classList.remove('doc-viewer-open');
            overlay.setAttribute('aria-hidden', 'true');
        };

        window.docViewerZoom = function (delta) {
            state.scale = Math.min(6, Math.max(0.5, state.scale + delta));
            applyTransform();
        };

        window.docViewerRotate = function (delta) {
            state.rotate += delta;
            applyTransform();
        };

        window.docViewerReset = function () {
            state.scale = 1; state.rotate = 0; state.x = 0; state.y = 0;
            applyTransform();
        };

        document.addEventListener('DOMContentLoaded', function () {
            var overlay = document.getElementById('docViewerOverlay');
            var stage = document.getElementById('docViewerStage');
            if (!overlay || !stage) return;

            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) docViewerClose();
            });

            document.addEventListener('keydown', function (e) {
                if (!overlay.classList.contains('doc-viewer-open')) return;
                if (e.key === 'Escape') docViewerClose();
                if (e.key === '+') docViewerZoom(0.25);
                if (e.key === '-') docViewerZoom(-0.25);
            });

            stage.addEventListener('wheel', function (e) {
                e.preventDefault();
                docViewerZoom(e.deltaY < 0 ? 0.15 : -0.15);
            }, { passive: false });

            stage.addEventListener('mousedown', function (e) {
                state.dragging = true;
                state.startX = e.clientX - state.x;
                state.startY = e.clientY - state.y;
                stage.classList.add('doc-viewer-dragging');
            });
            window.addEventListener('mousemove', function (e) {
                if (!state.dragging) return;
                state.x = e.clientX - state.startX;
                state.y = e.clientY - state.startY;
                applyTransform();
            });
            window.addEventListener('mouseup', function () {
                state.dragging = false;
                stage.classList.remove('doc-viewer-dragging');
            });

            // Support tactile basique (glisser à un doigt)
            stage.addEventListener('touchstart', function (e) {
                if (e.touches.length !== 1) return;
                state.dragging = true;
                state.startX = e.touches[0].clientX - state.x;
                state.startY = e.touches[0].clientY - state.y;
            }, { passive: true });
            stage.addEventListener('touchmove', function (e) {
                if (!state.dragging || e.touches.length !== 1) return;
                state.x = e.touches[0].clientX - state.startX;
                state.y = e.touches[0].clientY - state.startY;
                applyTransform();
            }, { passive: true });
            stage.addEventListener('touchend', function () { state.dragging = false; });
        });
    })();
</script>
