<?php
require_once 'includes/header.php';

$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? $_SESSION['user_id'] : 0);

if ($profile_id <= 0) {
    redirect('login.php');
}
?>

<div class="feed-container">

    <div id="profileContainer">
        <div class="text-center my-5">
            <div class="spinner-border text-pastel-purple"></div>
        </div>
    </div>

    <h4 class="posts-title">Posts</h4>

    <div id="profilePosts" class="posts-grid-instagram"></div>

</div>

<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">

            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="editProfileAlert" class="alert d-none"></div>

                <form id="editProfileForm">

                    <div class="mb-3">
                        <label class="form-label">Nickname</label>
                        <input type="text" class="form-control" id="editNickname">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" id="editBio" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Save Changes
                    </button>

                </form>

                <hr>

                <form id="avatarForm" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label">Upload Avatar</label>
                        <input type="file" class="form-control" name="avatar" accept="image/*" required>
                        <div class="form-text">JPG, PNG, or GIF up to 2MB</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Upload Avatar
                    </button>

                </form>

            </div>

        </div>
    </div>
</div>

<script>
const profileId = <?php echo $profile_id; ?>;
let currentProfile = null;

async function loadProfile() {
    try {
        const response = await fetch(`<?php echo BASE_URL; ?>/api/profile.php?id=${profileId}`);
        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        currentProfile = data.user;
        renderProfile(data.user);

        document.getElementById('editNickname').value = data.user.nickname || data.user.username;
        document.getElementById('editBio').value = data.user.bio || '';

    } catch (error) {
        document.getElementById('profileContainer').innerHTML = `
            <div class="alert alert-danger">${error.message}</div>
        `;
    }
}

function renderProfile(user) {
    const container = document.getElementById('profileContainer');

    container.innerHTML = `
        <div class="profile-header-instagram">
            <div class="profile-header-row">

                <div class="profile-avatar">
                    ${
                        user.profile_pic
                        ? `<img src="${user.profile_pic}" alt="Avatar">`
                        : '<i class="bi bi-person default-avatar"></i>'
                    }
                </div>

                <div class="profile-info">

                    <div class="profile-name-row">
                        <h2 class="profile-username">@${escapeHtml(user.username)}</h2>

                        ${
                            user.is_own_profile
                            ? `<button class="edit-profile-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    Edit Profile
                               </button>`
                            : ''
                        }
                    </div>

                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-number">${user.post_count}</span>
                            <span class="stat-label">Posts</span>
                        </div>
                    </div>

                    <div class="profile-bio">
                        <strong>${escapeHtml(user.nickname || user.username)}</strong>
                        <br>
                        ${escapeHtml(user.bio || 'No bio yet.')}
                    </div>

                    <div class="profile-joined">
                        Joined ${new Date(user.created_at).toLocaleDateString()}
                    </div>

                </div>

            </div>
        </div>
    `;
}

async function loadProfilePosts() {
    try {
        const response = await fetch(`<?php echo BASE_URL; ?>/api/posts.php?user_id=${profileId}&limit=50`);
        const data = await response.json();

        const grid = document.getElementById('profilePosts');

        if (!data.posts || data.posts.length === 0) {
            grid.innerHTML = '<p class="text-muted">No posts yet.</p>';
            return;
        }

        grid.innerHTML = data.posts.map(post => `
            <div class="grid-item">
                <a class="grid-link" href="<?php echo BASE_URL; ?>/post.php?id=${post.id}">
                    <div class="grid-image">
                        <img src="${post.image_path}" alt="${escapeHtml(post.title || 'Post')}">
                    </div>

                    <div class="grid-overlay">
                        <div class="grid-stats">
                            <span><i class="bi bi-arrow-up"></i> ${post.upvotes}</span>
                            <span><i class="bi bi-chat"></i> ${post.comments}</span>
                        </div>
                    </div>
                </a>
            </div>
        `).join('');

    } catch (error) {
        document.getElementById('profilePosts').innerHTML = `
            <div class="alert alert-danger">Failed to load posts.</div>
        `;
    }
}

document.getElementById('editProfileForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const alertBox = document.getElementById('editProfileAlert');

    const data = {
        nickname: document.getElementById('editNickname').value.trim(),
        bio: document.getElementById('editBio').value.trim()
    };

    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alertBox.classList.remove('d-none', 'alert-danger');
            alertBox.classList.add('alert-success');
            alertBox.textContent = 'Profile updated!';

            loadProfile();
        } else {
            alertBox.classList.remove('d-none', 'alert-success');
            alertBox.classList.add('alert-danger');
            alertBox.textContent = result.error || 'Update failed';
        }

    } catch (error) {
        alertBox.classList.remove('d-none', 'alert-success');
        alertBox.classList.add('alert-danger');
        alertBox.textContent = 'Network error';
    }
});

document.getElementById('avatarForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const alertBox = document.getElementById('editProfileAlert');
    const formData = new FormData(this);

    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/upload_avatar.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alertBox.classList.remove('d-none', 'alert-danger');
            alertBox.classList.add('alert-success');
            alertBox.textContent = 'Avatar updated!';

            loadProfile();
        } else {
            alertBox.classList.remove('d-none', 'alert-success');
            alertBox.classList.add('alert-danger');
            alertBox.textContent = result.error || 'Avatar upload failed';
        }

    } catch (error) {
        alertBox.classList.remove('d-none', 'alert-success');
        alertBox.classList.add('alert-danger');
        alertBox.textContent = 'Network error';
    }
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

loadProfile();
loadProfilePosts();
</script>

<?php
require_once 'includes/footer.php';
?>