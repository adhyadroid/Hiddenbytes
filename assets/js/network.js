/**
 * assets/js/network.js
 * Renders the connection graph on a plain <canvas> - no external
 * graph library. Relationship-specific edge colors, zoom controls,
 * and click-to-inspect for both nodes and edges.
 */

document.addEventListener('DOMContentLoaded', function () {
    const summaryBox = document.getElementById('network-summary');
    if (!summaryBox) return;
    const type = summaryBox.dataset.type;
    const id = summaryBox.dataset.id;

    const canvas = document.getElementById('network-canvas');
    const ctx = canvas.getContext('2d');
    const detailBox = document.getElementById('node-detail');
    const directBox = document.getElementById('direct-connections');
    const multihopBox = document.getElementById('multihop-connections');

    const NODE_COLOR = { donor: '#134074', company: '#9B6BE0', party: '#3B82D9' };
    const EDGE_COLOR = {
        'Donation': '#3B82D9',
        'Shared Address': '#22A55E',
        'Shared Phone': '#14B8A6',
        'Common Director': '#F59E0B',
        'Company Relationship': '#9B6BE0',
        'Associated Entity': '#9B6BE0',
    };
    const SELECTED = '#EEF4ED';

    let clusterData = null;
    let baseLayout = [];
    let layoutNodes = [];
    let layoutEdges = [];
    let selected = null;
    let scale = 1;

    function computeBaseLayout(cluster) {
        const w = canvas.width, h = canvas.height, cx = w / 2, cy = h / 2;
        const radius = Math.min(w, h) / 2 - 60;
        const n = cluster.nodes.length;
        return cluster.nodes.map((node, i) => {
            const angle = (2 * Math.PI * i) / n - Math.PI / 2;
            return { ox: radius * Math.cos(angle), oy: radius * Math.sin(angle), r: node.hop === 0 ? 19 : 13, node };
        });
    }
    function applyScale() {
        const cx = canvas.width / 2, cy = canvas.height / 2;
        layoutNodes = baseLayout.map((b) => ({ x: cx + b.ox * scale, y: cy + b.oy * scale, r: b.r, node: b.node }));
        const keyIndex = {};
        layoutNodes.forEach((ln, i) => { keyIndex[ln.node.type + ':' + ln.node.id] = i; });
        layoutEdges = clusterData.edges.map((e) => {
            const a = layoutNodes[keyIndex[e.from_type + ':' + e.from_id]];
            const b = layoutNodes[keyIndex[e.to_type + ':' + e.to_id]];
            return a && b ? { x1: a.x, y1: a.y, x2: b.x, y2: b.y, edge: e } : null;
        }).filter(Boolean);
    }

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        layoutEdges.forEach((le) => {
            const isSel = selected && selected.kind === 'edge' && selected.data === le.edge;
            ctx.strokeStyle = isSel ? SELECTED : (EDGE_COLOR[le.edge.relationship_type] || '#4A5568');
            ctx.lineWidth = isSel ? 3 : 2;
            ctx.beginPath(); ctx.moveTo(le.x1, le.y1); ctx.lineTo(le.x2, le.y2); ctx.stroke();
        });
        layoutNodes.forEach((ln) => {
            const isSel = selected && selected.kind === 'node' && selected.data === ln.node;
            ctx.fillStyle = isSel ? SELECTED : (NODE_COLOR[ln.node.type] || NODE_COLOR.donor);
            ctx.beginPath(); ctx.arc(ln.x, ln.y, ln.r, 0, Math.PI * 2); ctx.fill();
            ctx.fillStyle = '#FFFFFF';
            ctx.font = '10px "Lucida Sans", sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText((ln.node.name || '').split(' ').slice(0, 2).join(' '), ln.x, ln.y + ln.r + 13);
        });
    }

    function findNodeAt(x, y) { return layoutNodes.find((ln) => Math.hypot(ln.x - x, ln.y - y) <= ln.r + 4); }
    function findEdgeAt(x, y) {
        const tol = 7;
        return layoutEdges.find((le) => {
            const len = Math.hypot(le.x2 - le.x1, le.y2 - le.y1);
            if (len === 0) return false;
            const t = ((x - le.x1) * (le.x2 - le.x1) + (y - le.y1) * (le.y2 - le.y1)) / (len * len);
            if (t < 0 || t > 1) return false;
            const px = le.x1 + t * (le.x2 - le.x1), py = le.y1 + t * (le.y2 - le.y1);
            return Math.hypot(px - x, py - y) <= tol;
        });
    }

    canvas.addEventListener('click', function (evt) {
        const rect = canvas.getBoundingClientRect();
        const sx = canvas.width / rect.width, sy = canvas.height / rect.height;
        const x = (evt.clientX - rect.left) * sx, y = (evt.clientY - rect.top) * sy;
        const node = findNodeAt(x, y);
        if (node) {
            selected = { kind: 'node', data: node.node }; draw();
            detailBox.innerHTML = '<strong>' + escapeHtml(node.node.name || '') + '</strong>. ' + escapeHtml(node.node.type) +
                (node.node.hop === 0 ? ' (selected entity)' : ', ' + node.node.hop + ' hop(s) away') +
                ' - <a href="entity.php?type=' + encodeURIComponent(node.node.type) + '&id=' + encodeURIComponent(node.node.entity_id) + '">view entity</a>';
            return;
        }
        const edge = findEdgeAt(x, y);
        if (edge) {
            selected = { kind: 'edge', data: edge.edge }; draw();
            detailBox.innerHTML = '<strong>' + escapeHtml(edge.edge.relationship_type) + '</strong><br>' +
                escapeHtml(edge.edge.supporting_value) + '<br>Source: ' + escapeHtml(edge.edge.from_type + ' #' + edge.edge.from_id) +
                ' / Target: ' + escapeHtml(edge.edge.to_type + ' #' + edge.edge.to_id);
            return;
        }
        selected = null; detailBox.innerHTML = ''; draw();
    });

    document.getElementById('zoom-in')?.addEventListener('click', () => { scale = Math.min(2.5, scale * 1.2); applyScale(); draw(); });
    document.getElementById('zoom-out')?.addEventListener('click', () => { scale = Math.max(0.4, scale / 1.2); applyScale(); draw(); });
    document.getElementById('zoom-reset')?.addEventListener('click', () => { scale = 1; applyScale(); draw(); });
    document.getElementById('zoom-fit')?.addEventListener('click', () => { scale = 1; applyScale(); draw(); });

    fetch('../api/network.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id) + '&hops=4')
        .then((r) => r.json())
        .then((cluster) => {
            if (cluster.error) { summaryBox.innerHTML = '<p class="muted">' + escapeHtml(cluster.error) + '</p>'; return; }
            clusterData = cluster;
            baseLayout = computeBaseLayout(cluster);
            applyScale(); draw();

            const anchor = cluster.nodes.find((n) => n.hop === 0);
            const maxHop = Math.max(...cluster.nodes.map((n) => n.hop));
            summaryBox.innerHTML = '<p class="muted">Selected Entity: <strong>' + escapeHtml(anchor ? anchor.name : '') +
                '</strong>. ' + cluster.nodes.length + ' connected entities. Maximum hop depth: ' + maxHop + '.</p>';

            const direct = cluster.edges.filter((e) => e.from_id === anchor.id && e.from_type === anchor.type || (e.to_id === anchor.id && e.to_type === anchor.type));
            directBox.innerHTML = direct.length ? direct.map((e) =>
                '<p style="font-size:12px;margin:4px 0;">' + escapeHtml(e.relationship_type) + ': ' + escapeHtml(e.supporting_value) + '</p>'
            ).join('') : '<p class="muted">No direct connections.</p>';

            const multihop = cluster.nodes.filter((n) => n.hop >= 2);
            multihopBox.innerHTML = multihop.length ? multihop.map((n) =>
                '<p style="font-size:12px;margin:4px 0;">' + escapeHtml(n.name) + ' (' + n.hop + ' hops away)</p>'
            ).join('') : '<p class="muted">No multi-hop connections beyond direct relationships.</p>';
        });
});
