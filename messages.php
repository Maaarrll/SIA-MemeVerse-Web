<?php
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="form-card">
            <h3><i class="bi bi-chat-dots"></i> Messages</h3>

            <div id="conversationList">
                <p class="text-muted">Loading conversations...</p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="form-card">
            <div id="chatHeader">
                <h3>Select a conversation</h3>
                <p class="text-muted">Choose a user from the left side to start chatting.</p>
            </div>

            <div id="chatBox" style="height: 400px; overflow-y: auto; border: 1px solid #2a2a2a; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                <p class="text-muted">No conversation selected.</p>
            </div>

            <form id="messageForm" style="display: none;">
                <div class="input-group">
                    <input type="text" id="messageInput" class="form-control" placeholder="Type a message..." required>
                    <button class="btn btn-primary" type="submit">
                        Send
                    </button>
                </div>
            </form>

            <button id="deleteConversationBtn" class="btn btn-danger mt-3" style="display: none;">
                Delete Conversation
            </button>
        </div>
    </div>
</div>

<script>
let selectedUserId = null;
let selectedUsername = '';
let messageInterval = null;

const conversationList = document.getElementById('conversationList');
const chatHeader = document.getElementById('chatHeader');
const chatBox = document.getElementById('chatBox');
const messageForm = document.getElementById('messageForm');
const messageInput = document.getElementById('messageInput');
const deleteConversationBtn = document.getElementById('deleteConversationBtn');

async function loadConversations() {
    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/get_conversations.php');
        const data = await response.json();

        if (!data.success) {
            conversationList.innerHTML = `<div class="alert alert-danger">${data.error || 'Failed to load conversations'}</div>`;
            return;
        }

        if (!data.conversations || data.conversations.length === 0) {
            conversationList.innerHTML = '<p class="text-muted">No conversations yet.</p>';
            return;
        }

        conversationList.innerHTML = data.conversations.map(convo => `
            <div class="conversation-item"
                 onclick="openConversation(${convo.user_id}, '${escapeHtml(convo.username)}')"
                 style="cursor: pointer; padding: 12px; border-bottom: 1px solid #2a2a2a;">
                 
                <div class="d-flex align-items-center gap-2">
                    <div class="user-avatar">
                        ${
                            convo.avatar_url
                            ? `<img src="${convo.avatar_url}" alt="Avatar">`
                            : '<i class="bi bi-person"></i>'
                        }
                    </div>

                    <div style="flex: 1;">
                        <strong>@${escapeHtml(convo.username)}</strong>
                        <br>
                        <small class="text-muted">${escapeHtml(convo.last_message || '')}</small>
                    </div>

                    ${
                        convo.unread_count > 0
                        ? `<span class="badge bg-danger">${convo.unread_count}</span>`
                        : ''
                    }
                </div>
            </div>
        `).join('');

    } catch (error) {
        conversationList.innerHTML = '<div class="alert alert-danger">Network error loading conversations.</div>';
    }
}

function openConversation(userId, username) {
    selectedUserId = userId;
    selectedUsername = username;

    chatHeader.innerHTML = `
        <h3><i class="bi bi-person-circle"></i> @${escapeHtml(username)}</h3>
        <p class="text-muted">Chat conversation</p>
    `;

    messageForm.style.display = 'block';
    deleteConversationBtn.style.display = 'block';

    loadMessages();

    if (messageInterval) {
        clearInterval(messageInterval);
    }

    messageInterval = setInterval(loadMessages, 3000);
}

async function loadMessages() {
    if (!selectedUserId) return;

    try {
        const response = await fetch(`<?php echo BASE_URL; ?>/api/get_messages.php?with=${selectedUserId}`);
        const data = await response.json();

        if (!data.success) {
            chatBox.innerHTML = `<div class="alert alert-danger">${data.error || 'Failed to load messages'}</div>`;
            return;
        }

        if (!data.messages || data.messages.length === 0) {
            chatBox.innerHTML = '<p class="text-muted">No messages yet. Start the conversation!</p>';
            return;
        }

        chatBox.innerHTML = data.messages.map(msg => `
            <div style="margin-bottom: 10px; text-align: ${msg.is_mine ? 'right' : 'left'};">
                <div style="
                    display: inline-block;
                    max-width: 75%;
                    padding: 10px 14px;
                    border-radius: 16px;
                    background: ${msg.is_mine ? '#c7b9ff' : '#1a1a1a'};
                    color: ${msg.is_mine ? '#0a0a0a' : '#f0f0f0'};
                    border: 1px solid #2a2a2a;
                ">
                    ${escapeHtml(msg.message)}
                    <br>
                    <small style="font-size: 0.7rem; opacity: 0.8;">
                        ${new Date(msg.created_at).toLocaleString()}
                    </small>
                </div>
            </div>
        `).join('');

        chatBox.scrollTop = chatBox.scrollHeight;

        loadConversations();

    } catch (error) {
        chatBox.innerHTML = '<div class="alert alert-danger">Network error loading messages.</div>';
    }
}

messageForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!selectedUserId) return;

    const message = messageInput.value.trim();

    if (!message) return;

    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                receiver_id: selectedUserId,
                message: message
            })
        });

        const data = await response.json();

        if (data.success) {
            messageInput.value = '';
            loadMessages();
            loadConversations();
        } else {
            alert(data.error || 'Failed to send message');
        }

    } catch (error) {
        alert('Network error sending message');
    }
});

deleteConversationBtn.addEventListener('click', async function() {
    if (!selectedUserId) return;

    if (!confirm('Delete this conversation?')) return;

    const formData = new FormData();
    formData.append('user_id', selectedUserId);

    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/delete_conversation.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            selectedUserId = null;
            selectedUsername = '';

            chatHeader.innerHTML = `
                <h3>Select a conversation</h3>
                <p class="text-muted">Choose a user from the left side to start chatting.</p>
            `;

            chatBox.innerHTML = '<p class="text-muted">No conversation selected.</p>';
            messageForm.style.display = 'none';
            deleteConversationBtn.style.display = 'none';

            loadConversations();
        } else {
            alert(data.error || 'Failed to delete conversation');
        }

    } catch (error) {
        alert('Network error deleting conversation');
    }
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

loadConversations();
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>