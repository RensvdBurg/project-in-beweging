(() => {
  const endpoint = '/project-in-beweging/pages/activiteit/interactions.php';

  async function request(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        Accept: 'application/json',
        ...options.headers,
      },
    });
    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.error || 'De aanvraag is mislukt.');
    }

    return result;
  }

  function setStatus(container, message = '') {
    const status = container.querySelector('.activity-interaction-status');
    status.textContent = message;
    status.classList.toggle('hidden', message === '');
  }

  function setLikeState(button, liked, count) {
    button.dataset.liked = String(liked);
    button.setAttribute('aria-pressed', String(liked));
    button.setAttribute('aria-label', liked ? 'Vind ik niet meer leuk' : 'Vind ik leuk');
    button.querySelector('svg').setAttribute('fill', liked ? 'currentColor' : 'none');
    button.querySelector('[data-like-count]').textContent = String(count);
  }

  function renderComments(list, comments) {
    list.replaceChildren();

    if (comments.length === 0) {
      const empty = document.createElement('li');
      empty.className = 'text-sm text-muted';
      empty.textContent = 'Nog geen reacties.';
      list.append(empty);
      return;
    }

    for (const comment of comments) {
      const item = document.createElement('li');
      item.className = 'rounded-xl bg-app-bg px-3 py-2';

      const author = document.createElement('p');
      author.className = 'text-xs font-bold';
      author.textContent = comment.author;

      const body = document.createElement('p');
      body.className = 'mt-1 whitespace-pre-line break-words text-sm';
      body.textContent = comment.body;

      const time = document.createElement('time');
      time.className = 'mt-1 block text-[10px] font-medium text-muted';
      time.dateTime = comment.createdAt;
      time.textContent = new Intl.DateTimeFormat('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'short',
      }).format(new Date(comment.createdAt));

      item.append(author, body, time);
      list.append(item);
    }
  }

  async function loadComments(container) {
    const postId = container.dataset.activityPostId;
    const panel = container.querySelector('.activity-comments-panel');
    const list = container.querySelector('.activity-comments-list');
    const result = await request(`${endpoint}?post_id=${encodeURIComponent(postId)}`);
    renderComments(list, result.comments);
    panel.dataset.loaded = 'true';
  }

  document.addEventListener('click', async event => {
    const likeButton = event.target.closest('.activity-like-button');
    const commentsButton = event.target.closest('.activity-comments-toggle');

    if (!likeButton && !commentsButton) {
      return;
    }

    const container = event.target.closest('.activity-interactions');
    if (!container) {
      return;
    }

    setStatus(container);

    if (commentsButton) {
      const panel = container.querySelector('.activity-comments-panel');
      const isExpanded = commentsButton.getAttribute('aria-expanded') === 'true';
      commentsButton.setAttribute('aria-expanded', String(!isExpanded));
      panel.classList.toggle('hidden', isExpanded);
      if (isExpanded) {
        return;
      }

      try {
        await loadComments(container);
      } catch (error) {
        setStatus(container, error.message);
      }
      return;
    }

    likeButton.disabled = true;
    try {
      const result = await request(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': container.dataset.csrfToken,
        },
        body: JSON.stringify({
          action: 'like',
          postId: Number(container.dataset.activityPostId),
        }),
      });
      setLikeState(likeButton, result.liked, result.likeCount);
    } catch (error) {
      setStatus(container, error.message);
    } finally {
      likeButton.disabled = false;
    }
  });

  document.addEventListener('submit', async event => {
    const form = event.target.closest('.activity-comment-form');
    if (!form) {
      return;
    }

    event.preventDefault();
    const container = form.closest('.activity-interactions');
    const input = form.querySelector('.activity-comment-input');
    const submitButton = form.querySelector('button[type="submit"]');
    const body = input.value.trim();
    if (!body) {
      setStatus(container, 'Schrijf eerst een reactie.');
      return;
    }

    setStatus(container);
    submitButton.disabled = true;
    try {
      const result = await request(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': container.dataset.csrfToken,
        },
        body: JSON.stringify({
          action: 'comment',
          postId: Number(container.dataset.activityPostId),
          body,
        }),
      });
      container.querySelector('[data-comment-count]').textContent = String(result.commentCount);
      input.value = '';
      await loadComments(container);
    } catch (error) {
      setStatus(container, error.message);
    } finally {
      submitButton.disabled = false;
    }
  });
})();
