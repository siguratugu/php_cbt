// CBT MTsN 1 Mesuji - Global JS Utilities

// CSRF token (stored in meta tag if needed)
const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

// Generic fetch wrapper
async function apiPost(url, data) {
    const fd = data instanceof FormData ? data : (() => { const f = new FormData(); Object.entries(data).forEach(([k,v]) => { if(Array.isArray(v)) v.forEach(i=>f.append(k+'[]',i)); else f.append(k,v); }); return f; })();
    const res = await fetch(url, { method: 'POST', body: fd });
    return res.json();
}

// HTML escape
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Format date
function formatDate(str) {
    if (!str) return '-';
    const d = new Date(str);
    return d.toLocaleDateString('id-ID', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
}

// Countdown timer
function startCountdown(seconds, displayEl, onExpire) {
    let remaining = seconds;
    const interval = setInterval(() => {
        if (remaining <= 0) { clearInterval(interval); if(onExpire) onExpire(); return; }
        remaining--;
        const h = Math.floor(remaining/3600);
        const m = Math.floor((remaining%3600)/60);
        const s = remaining%60;
        displayEl.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if(remaining <= 60) displayEl.className = 'timer-danger text-2xl font-bold';
        else if(remaining <= 300) displayEl.className = 'timer-warning text-2xl font-bold';
    }, 1000);
    return interval;
}

// Init lucide on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    if(typeof lucide !== 'undefined') lucide.createIcons();
});
