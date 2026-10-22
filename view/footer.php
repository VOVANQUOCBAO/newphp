<footer class="bg-white border-top mt-auto py-4"><div class="container d-flex flex-wrap justify-content-between gap-3">
    <div><strong>ĐiệnThoạiZ</strong><p class="small text-muted mb-0">© <?=date('Y')?> ĐiệnThoạiZ</p></div>
    <nav class="d-flex flex-wrap gap-3"><a href="index.php?act=trangchu">Sản phẩm</a><a href="index.php?act=lichsumuahang">Đơn hàng</a><a href="index.php?act=hopthu">Hộp thư</a><a href="index.php?act=chinhsach">Chính sách mua hàng</a></nav>
</div></footer>

<!-- ========================================================
     TOAST THÔNG BÁO GÓC PHẢI MÀN HÌNH (5 GIÂY)
     ======================================================== -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1065;">
    <div id="cartToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
        <div class="d-flex align-items-center p-2">
            <div class="fs-3 me-2 ps-2 text-white">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="toast-body fw-semibold py-2">
                <span id="toastMessage">Đã thêm sản phẩm vào giỏ hàng!</span>
                <div class="small opacity-75 mt-1">Giỏ hàng đã được cập nhật tự động.</div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- ========================================================
     KHUNG CHATBOX / HỎI AI TÌM KIẾM SẢN PHẨM [24/6]
     ======================================================== -->
<!-- Nút tròn mở Chatbot AI -->
<div class="ai-chat-bubble" id="aiChatBubble" title="Hỏi Trợ lý AI tư vấn điện thoại" onclick="toggleAIChatbox()">
    <i class="fas fa-robot"></i>
</div>

<!-- Hộp thoại Chatbot AI -->
<div class="ai-chatbox-window" id="aiChatWindow">
    <div class="ai-chatbox-header">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold">Trợ Lý AI ĐiệnThoạiZ</h6>
                <small class="opacity-75">Tư vấn chọn mua thông minh 24/7</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" onclick="toggleAIChatbox()"></button>
    </div>

    <div class="ai-chatbox-body" id="aiChatBody">
        <div class="chat-bubble-msg chat-bubble-bot">
            👋 Xin chào bạn! Mình là Trợ lý AI của <strong>ĐiệnThoạiZ</strong>. Bạn đang muốn tìm dòng điện thoại như thế nào? Cứ hỏi mình nhé!
        </div>
        <div class="d-flex flex-wrap gap-1 mt-1">
            <button class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="askAIQuick('iPhone 16 mới')">iPhone 16 mới</button>
            <button class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="askAIQuick('Điện thoại dưới 15 triệu')">Dưới 15 triệu</button>
            <button class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="askAIQuick('Samsung AI')">Samsung AI</button>
            <button class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="askAIQuick('Chụp ảnh đẹp')">Chụp ảnh đẹp</button>
        </div>
    </div>

    <div class="ai-chatbox-footer">
        <form onsubmit="handleAISend(event)" class="d-flex gap-2">
            <input type="text" id="aiChatInput" class="form-control form-control-sm rounded-pill" placeholder="Hỏi AI: tìm điện thoại chụp ảnh, dưới 20tr..." autocomplete="off">
            <button type="submit" class="btn btn-primary btn-sm rounded-circle" style="width: 34px; height: 34px;">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<!-- ========================================================
     NHÚNG BOOTSTRAP 5 JS & SCRIPT XỬ LÝ TOAST, THEME, CHAT AI, HIỆU ỨNG MÙA
     ======================================================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
$footer_base_url = !empty($base_url) ? $base_url : (file_exists('view/css/theme.css') ? 'view' : (file_exists('../view/css/theme.css') ? '../view' : '../../view'));
?>
<!-- Script hiệu ứng 4 mùa (Hoa đào, Bãi biển cây dừa, Lá phong, Tuyết & Ông già Noel) -->

<script>
// Quản lý Toast giỏ hàng và sự kiện Mua hàng
document.addEventListener("DOMContentLoaded", function() {
    // Khôi phục tùy biến Mùa, Font, Nền từ LocalStorage
    restoreUserPreferences();
});

// Hàm đổi chủ đề mùa [17/6] [24/6]
function changeThemeSeason(season) {
    document.body.classList.remove('theme-spring', 'theme-summer', 'theme-autumn', 'theme-winter');
    if (season && season !== 'default') {
        document.body.classList.add(season);
    }
    localStorage.setItem('selected_season', season);
}

// Hàm đổi Font chữ
function changeFontFamily(font) {
    document.body.classList.remove('font-segoe', 'font-bevietnam', 'font-roboto', 'font-montserrat');
    if (font) {
        document.body.classList.add(font);
    }
    localStorage.setItem('selected_font', font);
}

// Hàm đổi Nền
function changeBackgroundMode(bg) {
    document.body.classList.remove('bg-light-gray', 'bg-white-pure', 'bg-soft-blue');
    if (bg) {
        document.body.classList.add(bg);
    }
    localStorage.setItem('selected_bg', bg);
}

// Khôi phục cài đặt lưu trữ cá nhân (Font & Nền)
function restoreUserPreferences() {
    const savedFont = localStorage.getItem('selected_font');
    if (savedFont) {
        changeFontFamily(savedFont);
        const sel = document.getElementById('fontSelect');
        if (sel) sel.value = savedFont;
    }

    const savedBg = localStorage.getItem('selected_bg');
    if (savedBg) {
        changeBackgroundMode(savedBg);
        const sel = document.getElementById('bgSelect');
        if (sel) sel.value = savedBg;
    }
}

// Đóng mở Chatbox AI [24/6]
function toggleAIChatbox() {
    const win = document.getElementById('aiChatWindow');
    if (win.style.display === 'flex') {
        win.style.display = 'none';
    } else {
        win.style.display = 'flex';
        document.getElementById('aiChatInput').focus();
    }
}

// Bấm nhanh gợi ý AI
function askAIQuick(text) {
    document.getElementById('aiChatInput').value = text;
    handleAISend(new Event('submit'));
}

// Xử lý AI phân tích và gợi ý sản phẩm
async function handleAISend(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('aiChatInput');
    const query = input.value.trim();
    if (!query) return;

    const chatBody = document.getElementById('aiChatBody');

    const userMsg = document.createElement('div');
    userMsg.className = 'chat-bubble-msg chat-bubble-user';
    userMsg.textContent = query;
    chatBody.appendChild(userMsg);
    input.value = '';
    chatBody.scrollTop = chatBody.scrollHeight;

    const loadingMsg = document.createElement('div');
    loadingMsg.className = 'chat-bubble-msg chat-bubble-bot';
    loadingMsg.textContent = 'Đang tư vấn cho bạn...';
    chatBody.appendChild(loadingMsg);
    chatBody.scrollTop = chatBody.scrollHeight;

    try {
        const response = await fetch('index.php?act=chatbot&ajax=1', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ message: query })
        });

        const data = await response.json();
        const reply = (data && data.reply) ? data.reply : 'Xin lỗi, hiện tại AI chưa phản hồi được. Bạn có thể thử lại sau.';

        const botMsg = document.createElement('div');
        botMsg.className = 'chat-bubble-msg chat-bubble-bot';
        botMsg.textContent = reply;
        botMsg.style.whiteSpace = "pre-wrap";
        chatBody.replaceChild(botMsg, loadingMsg);
        chatBody.scrollTop = chatBody.scrollHeight;
    } catch (error) {
        const botMsg = document.createElement('div');
        botMsg.className = 'chat-bubble-msg chat-bubble-bot';
        botMsg.textContent = 'AI tạm thời không phản hồi. Vui lòng thử lại sau vài giây.';
        chatBody.replaceChild(botMsg, loadingMsg);
        chatBody.scrollTop = chatBody.scrollHeight;
    }
}
</script>
</body>
</html>
