<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENDURE-Net — Diagramme de flux</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; min-height: 100vh; }

        /* ── top bar ──────────────────────────────────────────────────── */
        .top-bar {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .top-bar a.back {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            transition: color .15s;
        }
        .top-bar a.back:hover { color: #fff; }
        .top-bar h1 {
            flex: 1;
            font-size: 15px;
            font-weight: 600;
            white-space: nowrap;
        }
        .btn-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            white-space: nowrap;
        }
        .btn:hover:not(:disabled) { background: rgba(255,255,255,0.28); }
        .btn:disabled { opacity: .6; cursor: default; }
        .btn-png { background: rgba(22,163,74,.25); border-color: rgba(22,163,74,.5); }
        .btn-png:hover:not(:disabled) { background: rgba(22,163,74,.45); }
        .btn-jpg { background: rgba(217,119,6,.25); border-color: rgba(217,119,6,.5); }
        .btn-jpg:hover:not(:disabled) { background: rgba(217,119,6,.45); }

        /* ── spinner ──────────────────────────────────────────────────── */
        .spinner {
            display: none;
            width: 11px;
            height: 11px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .55s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── diagram ──────────────────────────────────────────────────── */
        .diagram-wrap {
            padding: 24px;
            overflow-x: auto;
        }
        #flow-svg {
            display: block;
            border-radius: 10px;
            box-shadow: 0 4px 28px rgba(0,0,0,.12);
            background: white;
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a class="back" href="{{ route('dashboard') }}">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Tableau de bord
    </a>
    <h1>Diagramme de flux — ENDURE-Net</h1>
    <div class="btn-group">
        <button class="btn" onclick="dlFlow('svg')">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            SVG
        </button>
        <button class="btn btn-png" id="btn-png" onclick="dlFlow('png')">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            PNG
            <span class="spinner" id="sp-png"></span>
        </button>
        <button class="btn btn-jpg" id="btn-jpg" onclick="dlFlow('jpg')">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            JPG
            <span class="spinner" id="sp-jpg"></span>
        </button>
    </div>
</div>

<div class="diagram-wrap">
    {!! preg_replace('/<\?xml[^?]*\?>\s*/', '', $svg) !!}
</div>

<script>
function dlFlow(fmt) {
    const svg = document.getElementById('flow-svg');
    if (!svg) { alert('Diagramme introuvable.'); return; }

    const w = parseInt(svg.getAttribute('width'))  || 1200;
    const h = parseInt(svg.getAttribute('height')) || 680;

    /* serialise with namespace fixes so Firefox/Chrome both work */
    const serial = new XMLSerializer().serializeToString(svg);

    if (fmt === 'svg') {
        const full = '<?xml version="1.0" encoding="UTF-8"?>\n' + serial;
        const blob = new Blob([full], { type: 'image/svg+xml;charset=utf-8' });
        triggerDl(URL.createObjectURL(blob), 'ENDURE-Net-Flow.svg');
        return;
    }

    const btn = document.getElementById('btn-' + fmt);
    const sp  = document.getElementById('sp-'  + fmt);
    if (btn) btn.disabled = true;
    if (sp)  sp.style.display = 'inline-block';

    /* encode SVG as data-URL (handles unicode chars) */
    const dataUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(serial);

    const img = new Image();
    img.onload = function () {
        const scale  = 2;                      /* 2× for retina quality */
        const canvas = document.createElement('canvas');
        canvas.width  = w * scale;
        canvas.height = h * scale;
        const ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);
        if (fmt === 'jpg') {                   /* JPG needs opaque white bg */
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, w, h);
        }
        ctx.drawImage(img, 0, 0, w, h);
        const mime = fmt === 'jpg' ? 'image/jpeg' : 'image/png';
        canvas.toBlob(function (blob) {
            triggerDl(URL.createObjectURL(blob),
                      'ENDURE-Net-Flow.' + (fmt === 'jpg' ? 'jpg' : 'png'));
            if (btn) btn.disabled = false;
            if (sp)  sp.style.display = 'none';
        }, mime, 0.93);
    };
    img.onerror = function () {
        alert('Erreur de conversion — essayez le format SVG.');
        if (btn) btn.disabled = false;
        if (sp)  sp.style.display = 'none';
    };
    img.src = dataUrl;
}

function triggerDl(url, filename) {
    const a = document.createElement('a');
    a.href     = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 10000);
}
</script>

</body>
</html>
