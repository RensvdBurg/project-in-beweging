(() => {
    const searchInput = document.getElementById('challenge-search');
    const challengeList = document.getElementById('challenge-list');
    const emptyMessage = document.getElementById('challenge-search-empty');

    if (!(searchInput instanceof HTMLInputElement) || !challengeList || !emptyMessage) {
        return;
    }

    const challenges = Array.from(challengeList.querySelectorAll('li[data-search]'));
    const normalize = (value) => value
        .toLocaleLowerCase('nl')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    searchInput.addEventListener('input', () => {
        const query = normalize(searchInput.value.trim());
        let visibleCount = 0;

        challenges.forEach((challenge) => {
            const matches = normalize(challenge.dataset.search || '').includes(query);
            challenge.hidden = !matches;
            visibleCount += Number(matches);
        });

        emptyMessage.hidden = visibleCount > 0;
    });
})();
