<style>
.existing-doc-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 8px;
    background: #f8fafc;
}
.existing-doc-item .doc-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    flex: 1;
}
.existing-doc-item .doc-info i {
    font-size: 20px;
    color: #64748b;
    flex-shrink: 0;
}
.existing-doc-item .doc-name {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.existing-doc-item .doc-meta {
    font-size: 11px;
    color: #94a3b8;
}
.existing-doc-item .btn-remove-doc {
    background: none;
    border: 1px solid #fca5a5;
    color: #ef4444;
    font-size: 12px;
    padding: 3px 10px;
    border-radius: 5px;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.15s;
}
.existing-doc-item .btn-remove-doc:hover {
    background: #fef2f2;
    border-color: #ef4444;
}
.existing-doc-item .btn-remove-doc:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<script>
window.zmcRemoveDocument = function(docId, el, deleteUrl) {
    if (!confirm('Are you sure you want to remove this document? This cannot be undone.')) return;

    const btn = el;
    btn.disabled = true;
    btn.textContent = 'Removing...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const item = btn.closest('.existing-doc-item');
            item.style.transition = 'opacity 0.3s, max-height 0.3s';
            item.style.opacity = '0';
            item.style.maxHeight = '0';
            item.style.overflow = 'hidden';
            item.style.marginBottom = '0';
            item.style.padding = '0';
            setTimeout(() => item.remove(), 350);
        } else {
            alert(data.message || 'Failed to remove document.');
            btn.disabled = false;
            btn.textContent = 'Remove';
        }
    })
    .catch(() => {
        alert('Error removing document. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Remove';
    });
};
</script>
