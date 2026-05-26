const openStates = {};

function toggleDoc(i) {
    const body = document.getElementById('body-' + i);
    const json = document.getElementById('json-' + i);
    if (!openStates[i]) {
        openStates[i] = true;
        body.classList.add('open');
        if (!json.dataset.rendered) {
            json.innerHTML = syntaxHighlight(window.__docs[i]);
            json.dataset.rendered = '1';
        }
    } else {
        openStates[i] = false;
        body.classList.remove('open');
    }
}

function syntaxHighlight(obj) {
    const json = JSON.stringify(obj, null, 2);
    return json.replace(/("(\\u[\dA-Fa-f]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+\.?\d*(?:[eE][+\-]?\d+)?)/g, match => {
        if (/^"/.test(match)) {
            if (/:$/.test(match)) return '<span class="jk">' + match + '</span>';
            return '<span class="js">' + match + '</span>';
        }
        if (/true|false|null/.test(match)) return '<span class="jb">' + match + '</span>';
        return '<span class="jn">' + match + '</span>';
    });
}

function confirmDelDoc(id) {
    const collection = window.StorageData.collection;
    document.getElementById('delDocCode').textContent = 'db.' + collection + '.deleteOne({ _id: ObjectId("' + id + '") })';
    document.getElementById('delDocForm').action = '/admin/storage/' + collection + '/' + id;
    document.getElementById('delDocModal').style.display = 'flex';
}
function closeModal(e) {
    if (e.target.classList.contains('st-modal-overlay'))
        e.target.style.display = 'none';
}
