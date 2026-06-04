<?php
require_once 'includes/header.php';
?>

<div class="feed-container" id="posts-container">
    <!-- Posts will load here -->
</div>

<div id="loading-spinner" class="text-center my-5" style="display: none;">
    <div class="spinner-border text-pastel-purple" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div id="end-message" class="text-center my-4 text-muted" style="display: none;">
    No more memes to show
</div>

<script>
let currentPage = 1;
let loading = false;
let hasMore = true;

const postsContainer = document.getElementById('posts-container');
const spinner = document.getElementById('loading-spinner');
const endMessage = document.getElementById('end-message');

async function loadPosts() {
    if (loading || !hasMore) return;

    loading = true;
    spinner.style.display = 'block';

    try {
        const response = await fetch(`<?php echo BASE_URL; ?>/api/posts.php?page=${currentPage}&limit=10`);
        const data = await response.json();

        if (data.error) {
            postsContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            return;
        }

        if (data.posts && data.posts.length > 0) {
            renderPosts(data.posts);
            currentPage++;
            hasMore = data.pagination.has_more;
        } else {
            hasMore = false;
        }

        if (!hasMore) {
            endMessage.style.display = 'block';
        }

    } catch (error) {
        postsContainer.innerHTML = '<div class="alert alert-danger">Failed to load posts. Please refresh.</div>';
    } finally {
        loading = false;
        spinner.style.display = 'none';
    }
}

function renderPosts(posts) {
    posts.forEach(post => {
        const card = document.createElement('div');
        card.className = 'feed-card';

        const time = new Date(post.created_at).toLocaleDateString();
        const categoryIcon = getCategoryIcon(post.category.slug);

        card.innerHTML = `
            <div class="card-header-custom">
                <div class="user-avatar">
                    ${
                        post.user.profile_pic
                        ? `<img src="${post.user.profile_pic}" alt="Avatar">`
                        : '<i class="bi bi-person"></i>'
                    }
                </div>

                <div class="user-info">
                    <a href="<?php echo BASE_URL; ?>/profile.php?id=${post.user.id}" class="user-name">
                        @${escapeHtml(post.user.username)}
                    </a>
                    <div class="post-time">${time}</div>
                </div>

                <span class="category-badge">
                    <i class="bi ${categoryIcon}"></i> ${escapeHtml(post.category.name)}
                </span>
            </div>

            <div class="feed-image">
                <img src="${post.image_path}" alt="${escapeHtml(post.title || 'Meme')}">
            </div>

            ${
                post.title
                ? `<div class="px-3 pt-3 fw-bold">${escapeHtml(post.title)}</div>`
                : ''
            }

            ${
                post.description
                ? `<div class="card-description">${escapeHtml(post.description)}</div>`
                : ''
            }

            <div class="card-actions">
                <div class="action-buttons">
                    <button class="action-btn vote-btn" data-post-id="${post.id}" data-vote="1">
                        <i class="bi bi-arrow-up"></i>
                        <span class="vote-count up-${post.id}">${post.upvotes}</span>
                    </button>

                    <button class="action-btn vote-btn" data-post-id="${post.id}" data-vote="-1">
                        <i class="bi bi-arrow-down"></i>
                        <span class="vote-count down-${post.id}">${post.downvotes}</span>
                    </button>

                    <a href="<?php echo BASE_URL; ?>/post.php?id=${post.id}" class="action-btn comment-link">
                        <i class="bi bi-chat"></i>
                        <span class="vote-count">${post.comments}</span>
                    </a>
                </div>

                <div class="post-footer">
                    Posted by 
                    <a href="<?php echo BASE_URL; ?>/profile.php?id=${post.user.id}">
                        @${escapeHtml(post.user.username)}
                    </a>
                </div>
            </div>
        `;

        postsContainer.appendChild(card);
    });

    document.querySelectorAll('.vote-btn').forEach(btn => {
        btn.removeEventListener('click', voteHandler);
        btn.addEventListener('click', voteHandler);
    });
}

async function voteHandler(e) {
    const btn = e.currentTarget;
    const postId = btn.dataset.postId;
    const vote = parseInt(btn.dataset.vote);

    const upSpan = document.querySelector(`.up-${postId}`);
    const downSpan = document.querySelector(`.down-${postId}`);

    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/vote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                vote: vote
            })
        });

        const result = await response.json();

        if (response.status === 401) {
            window.location.href = '<?php echo BASE_URL; ?>/login.php';
            return;
        }

        if (result.success) {
            upSpan.textContent = result.upvotes;
            downSpan.textContent = result.downvotes;
        }

    } catch (error) {
        console.error('Vote error:', error);
    }
}

function getCategoryIcon(slug) {
    const icons = {
        funny: 'bi-emoji-laughing',
        animals: 'bi-paw',
        music: 'bi-music-note-beamed',
        tv: 'bi-tv',
        games: 'bi-controller',
        movie: 'bi-film',
        sport: 'bi-trophy',
        foods: 'bi-cup-hot',
        travel: 'bi-airplane'
    };

    return icons[slug] || 'bi-tag';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
        loadPosts();
    }
});

loadPosts();
</script>

<?php
require_once 'includes/footer.php';
?>  