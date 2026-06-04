<?php
require_once 'includes/header.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    redirect('index.php');
}
?>

<div id="post-container" class="feed-container"></div>

<div id="loading" class="text-center my-5">
    <div class="spinner-border text-pastel-purple"></div>
</div>

<div class="feed-container mt-4">
    <h4>Comments</h4>

    <div id="comments-section" class="mt-3"></div>

    <?php if (isLoggedIn()): ?>
        <div class="form-card mt-3">
            <h5>Add a Comment</h5>

            <form id="commentForm">
                <textarea 
                    class="form-control mb-2" 
                    rows="3" 
                    placeholder="Write something..." 
                    required></textarea>

                <button type="submit" class="btn btn-primary">
                    Post Comment
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-info mt-3">
            Login to add a comment.
        </div>
    <?php endif; ?>
</div>

<script>
const postId = <?php echo $post_id; ?>;
let currentUserVote = 0;

async function loadPost() {
    try {
        const response = await fetch(`<?php echo BASE_URL; ?>/api/post.php?id=${postId}`);
        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        renderPost(data.post);

        currentUserVote = data.user_vote;
        renderVoteButtons(data.post.upvotes, data.post.downvotes);

    } catch (error) {
        document.getElementById('post-container').innerHTML = `
            <div class="alert alert-danger">${error.message}</div>
        `;
    } finally {
        document.getElementById('loading').style.display = 'none';
    }
}

function renderPost(post) {
    const container = document.getElementById('post-container');
    const time = new Date(post.created_at).toLocaleString();
    const categoryIcon = getCategoryIcon(post.category.slug);

    container.innerHTML = `
        <div class="feed-card">
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
                    <button class="action-btn" id="upvoteBtn">
                        <i class="bi bi-arrow-up"></i>
                        <span id="upvoteCount">${post.upvotes}</span>
                    </button>

                    <button class="action-btn" id="downvoteBtn">
                        <i class="bi bi-arrow-down"></i>
                        <span id="downvoteCount">${post.downvotes}</span>
                    </button>

                    <span class="action-btn">
                        <i class="bi bi-chat"></i>
                        <span id="commentCount">0</span>
                    </span>
                </div>

                <div class="post-footer">
                    Posted by 
                    <a href="<?php echo BASE_URL; ?>/profile.php?id=${post.user.id}">
                        @${escapeHtml(post.user.username)}
                    </a>
                </div>
            </div>
        </div>
    `;
}

function renderVoteButtons(upvotes, downvotes) {
    const upBtn = document.getElementById('upvoteBtn');
    const downBtn = document.getElementById('downvoteBtn');

    document.getElementById('upvoteCount').textContent = upvotes;
    document.getElementById('downvoteCount').textContent = downvotes;

    if (currentUserVote === 1) {
        upBtn.classList.add('text-pastel-purple');
    }

    if (currentUserVote === -1) {
        downBtn.classList.add('text-pastel-purple');
    }

    upBtn.addEventListener('click', () => vote(1));
    downBtn.addEventListener('click', () => vote(-1));
}

async function vote(value) {
    const newVote = value === currentUserVote ? 0 : value;

    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/vote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                vote: newVote
            })
        });

        const result = await response.json();

        console.log(result);

        if (response.status === 401) {
            window.location.href = '<?php echo BASE_URL; ?>/login.php';
            return;
        }

        if (result.success) {
            currentUserVote = result.user_vote;

            document.getElementById('upvoteCount').textContent = result.upvotes;
            document.getElementById('downvoteCount').textContent = result.downvotes;

            document.getElementById('upvoteBtn').classList.toggle('text-pastel-purple', currentUserVote === 1);
            document.getElementById('downvoteBtn').classList.toggle('text-pastel-purple', currentUserVote === -1);
        } else {
            alert(result.error || 'Vote failed');
        }

    } catch (error) {
        console.error('Vote error:', error);
        alert('Vote network error');
    }
}

async function loadComments() {
    try {
        const url = `<?php echo BASE_URL; ?>/api/comments.php?post_id=${postId}`;
        console.log('COMMENTS URL:', url);

        const response = await fetch(url);
        const text = await response.text();

        console.log('COMMENTS STATUS:', response.status);
        console.log('COMMENTS RAW:', text);

        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {
            document.getElementById('comments-section').innerHTML = `
                <div class="alert alert-danger">
                    Invalid comments response. Check F12 Console.
                </div>
            `;
            return;
        }

        const section = document.getElementById('comments-section');

        if (data.error) {
            section.innerHTML = `
                <div class="alert alert-danger">${data.error}</div>
            `;
            return;
        }

        if (!data.comments || data.comments.length === 0) {
            section.innerHTML = '<p class="text-muted">No comments yet.</p>';

            const count = document.getElementById('commentCount');
            if (count) {
                count.textContent = 0;
            }

            return;
        }

        const count = document.getElementById('commentCount');
        if (count) {
            count.textContent = data.comments.length;
        }

        section.innerHTML = data.comments.map(comment => `
            <div class="feed-card p-3 mb-2">
                <div class="d-flex justify-content-between">
                    <strong>@${escapeHtml(comment.username)}</strong>
                    <small class="text-muted">${new Date(comment.created_at).toLocaleString()}</small>
                </div>

                <p class="mb-0 mt-2">${escapeHtml(comment.content)}</p>
            </div>
        `).join('');

    } catch (error) {
        console.error('COMMENTS FETCH ERROR:', error);

        document.getElementById('comments-section').innerHTML = `
            <div class="alert alert-danger">
                Failed to load comments.
            </div>
        `;
    }
}

<?php if (isLoggedIn()): ?>
document.getElementById('commentForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const textarea = this.querySelector('textarea');
    const content = textarea.value.trim();

    if (!content) return;

    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/comments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                content: content
            })
        });

        const result = await response.json();

        if (result.success) {
            textarea.value = '';
            loadComments();
        } else {
            alert(result.error || 'Failed to add comment');
        }

    } catch (error) {
        alert('Network error');
    }
});
<?php endif; ?>

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

loadPost();
loadComments();
</script>

<?php
require_once 'includes/footer.php';
?>