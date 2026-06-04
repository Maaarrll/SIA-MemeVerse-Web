<?php
require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    redirect('index.php');
}

$stmt = $conn->prepare("SELECT id, name, slug FROM categories WHERE slug = ? LIMIT 1");

if (!$stmt) {
    die("Category prepare failed: " . $conn->error);
}

$stmt->bind_param('s', $slug);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Category not found: " . htmlspecialchars($slug));
}

$category = $result->fetch_assoc();

require_once __DIR__ . '/includes/header.php';
?>

<div class="feed-container">

    <div class="feed-card p-4 mb-4 text-center">
        <h2 class="mb-1">
            <i class="bi bi-tag"></i>
            <?php echo htmlspecialchars($category['name']); ?>
        </h2>
        <p class="text-muted mb-0">
            Browsing memes from this category
        </p>
    </div>

    <div id="posts-container"></div>

    <div id="loading-spinner" class="text-center my-5" style="display: none;">
        <div class="spinner-border text-pastel-purple"></div>
    </div>

    <div id="end-message" class="text-center my-4 text-muted" style="display: none;">
        No more memes in this category
    </div>

</div>

<script>
let currentPage = 1;
let loading = false;
let hasMore = true;

const categorySlug = "<?php echo htmlspecialchars($category['slug']); ?>";
const postsContainer = document.getElementById('posts-container');
const spinner = document.getElementById('loading-spinner');
const endMessage = document.getElementById('end-message');

async function loadPosts() {
    if (loading || !hasMore) return;

    loading = true;
    spinner.style.display = 'block';

    try {
        const url = `<?php echo BASE_URL; ?>/api/posts.php?page=${currentPage}&limit=10&category_slug=${categorySlug}`;
        console.log("CATEGORY POSTS URL:", url);

        const response = await fetch(url);
        const text = await response.text();
        console.log("CATEGORY POSTS RESPONSE:", text);

        let data = JSON.parse(text);

        if (data.error) {
            postsContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            return;
        }

        if (data.posts && data.posts.length > 0) {
            renderPosts(data.posts);
            currentPage++;
            hasMore = data.pagination.has_more;
        } else {
            postsContainer.innerHTML = '<p class="text-muted text-center">No posts in this category yet.</p>';
            hasMore = false;
        }

        if (!hasMore) {
            endMessage.style.display = 'block';
        }

    } catch (error) {
        console.error(error);
        postsContainer.innerHTML = '<div class="alert alert-danger">Failed to load category posts. Check Console.</div>';
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
                    <i class="bi bi-tag"></i> ${escapeHtml(post.category.name)}
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
        } else {
            alert(result.error || 'Vote failed');
        }

    } catch (error) {
        alert('Vote network error');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
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
require_once __DIR__ . '/includes/footer.php';
?>