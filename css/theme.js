document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('theme-select');
    const savedTheme = localStorage.getItem('selectedTheme') || 'theme-light';
    document.body.classList.add(savedTheme);
    if (select) select.value = savedTheme;

    select?.addEventListener('change', function () {
        document.body.classList.remove('theme-light', 'theme-grey', 'theme-dark');
        document.body.classList.add(this.value);
        localStorage.setItem('selectedTheme', this.value);
    });

    // Handle media expand/collapse
    document.addEventListener('click', function (e) {
        const media = e.target;
        if (media.tagName === 'IMG' || media.tagName === 'VIDEO') {
            const isExpanded = media.dataset.expanded === 'true';
            media.style.width = isExpanded ? '150px' : '100%';
            media.style.height = isExpanded ? '150px' : 'auto';
            media.dataset.expanded = !isExpanded;
        }
    });
});
