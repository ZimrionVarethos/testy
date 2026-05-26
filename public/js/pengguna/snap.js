const snapToken = window.SnapData.snapToken;
const finishUrl = window.SnapData.finishUrl;
const expiredAt = window.SnapData.expiredAt;

function formatCountdown(ms) {
    if (ms <= 0) return 'sudah kedaluwarsa';
    const h = Math.floor(ms / 3600000);
    const m = Math.floor((ms % 3600000) / 60000);
    const s = Math.floor((ms % 60000) / 1000);
    if (h > 0) return `${h} jam ${m} menit lagi`;
    if (m > 0) return `${m} menit ${s} detik lagi`;
    return `${s} detik lagi`;
}

if (expiredAt) {
    const deadline = new Date(expiredAt).getTime();
    function tick() {
        const left = deadline - Date.now();
        const label = formatCountdown(left);
        const el1 = document.getElementById('pay-countdown');
        const el2 = document.getElementById('banner-countdown');
        if (el1) el1.textContent = label;
        if (el2) el2.textContent = label;
        if (left <= 0) {
            document.getElementById('pay-button').disabled = true;
            document.getElementById('pay-button').textContent = 'Waktu pembayaran habis';
            document.getElementById('pay-button').className =
                'w-full py-3 bg-gray-300 text-gray-500 font-semibold rounded-lg cursor-not-allowed';
            clearInterval(timer);
        }
    }
    tick();
    const timer = setInterval(tick, 1000);
}

function openSnap() {
    document.getElementById('close-banner').classList.add('hidden');
    document.getElementById('pay-button').classList.remove('hidden');

    snap.pay(snapToken, {
        onSuccess(result) {
            window.location.href = finishUrl;
        },
        onPending(result) {
            window.location.href = finishUrl;
        },
        onError(result) {
            alert('Pembayaran gagal. Silakan coba lagi.');
        },
        onClose() {
            document.getElementById('close-banner').classList.remove('hidden');
        },
    });
}

document.getElementById('pay-button').addEventListener('click', openSnap);
document.getElementById('reopen-button').addEventListener('click', openSnap);
