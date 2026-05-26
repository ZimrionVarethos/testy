function confirmDrop(name, count) {
    document.getElementById('modalCode').textContent = 'db.' + name + '.drop()';
    document.getElementById('modalCount').textContent = count.toLocaleString();
    document.getElementById('dropForm').action = '/admin/storage/' + name;
    document.getElementById('dropModal').style.display = 'flex';
}
function closeModal(e) {
    if (e.target.id === 'dropModal') document.getElementById('dropModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const fill = document.querySelector('.st-bar-fill');
    if (fill) {
        const w = fill.style.width;
        fill.style.width = '0';
        setTimeout(() => fill.style.width = w, 100);
    }
});
