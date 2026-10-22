/**
 * ==========================================================================
 * BỘ ĐIỀU KHIỂN HIỆU ỨNG GIAO DIỆN THEO 4 MÙA (SEASON EFFECTS ENGINE)
 * - Mùa Xuân: Tone Đỏ Tết may mắn, cánh HOA MAI VÀNG rơi bay bay, cành mai vàng khoe sắc
 * - Mùa Hạ: Bãi biển, cây dừa nhiệt đới đung đưa, sóng biển, mặt trời rực rỡ
 * - Mùa Thu: Lá phong rơi bay bay với màu sắc lá đỏ, vàng ấm áp
 * - Mùa Đông: Tuyết rơi lấp lánh, Ông già Noel & Cỗ xe Tuần Lộc bay qua bầu trời
 * 
 * PHÂN QUYỀN:
 * - Khách hàng / Người dùng bình thường: Chỉ hiển thị hiệu ứng theo cài đặt do Admin cấu hình (theme.json).
 *   Không hiển thị nút bấm tự đổi mùa trên giao diện người dùng.
 * - Quản trị viên (Admin): Có toàn quyền tùy chỉnh mùa, font chữ, màu nền và xem trước trực tiếp.
 * ==========================================================================
 */

const SeasonEffects = (function () {
    let currentSeason = 'default';
    let containerEl = null;
    let isEffectsEnabled = true;

    // Các màu lá phong mùa thu
    const mapleColors = ['#d63031', '#e17055', '#e67e22', '#d35400', '#f39c12', '#b94600', '#c0392b'];

    // =========================================================================
    // 1. MÙA XUÂN: CÁNH HOA MAI VÀNG RƠI & CÀNH MAI VÀNG TẾT
    // =========================================================================

    // Mẫu SVG Cánh Hoa Mai Vàng (hoặc Bông mai vàng 5 cánh mini)
    function getMaiVangPetalSvg(size, id) {
        // Luân phiên sinh cánh hoa mai đơn lẻ hoặc bông hoa mai 5 cánh mini
        const isFullFlower = id % 3 === 0;

        if (isFullFlower) {
            // Bông hoa mai vàng 5 cánh mini nở rộ
            return `
            <svg viewBox="0 0 50 50" width="${size}" height="${size}" style="filter: drop-shadow(0 2px 4px rgba(215, 0, 24, 0.25));">
                <defs>
                    <radialGradient id="maiFullGrad_${id}" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#fff9c4" />
                        <stop offset="60%" stop-color="#ffd700" />
                        <stop offset="100%" stop-color="#ffb300" />
                    </radialGradient>
                </defs>
                <!-- 5 cánh hoa mai vàng -->
                <circle cx="25" cy="14" r="8.5" fill="url(#maiFullGrad_${id})" />
                <circle cx="34" cy="21" r="8.5" fill="url(#maiFullGrad_${id})" />
                <circle cx="31" cy="32" r="8.5" fill="url(#maiFullGrad_${id})" />
                <circle cx="19" cy="32" r="8.5" fill="url(#maiFullGrad_${id})" />
                <circle cx="16" cy="21" r="8.5" fill="url(#maiFullGrad_${id})" />
                <!-- Nhụy hoa đỏ cam Tết -->
                <circle cx="25" cy="25" r="4.5" fill="#d63031" />
                <circle cx="25" cy="25" r="2.5" fill="#f39c12" />
                <circle cx="23" cy="23" r="1" fill="#fff" />
                <circle cx="27" cy="24" r="1" fill="#fff" />
            </svg>`;
        }

        // Cánh hoa mai vàng đơn lẻ chao lượn trong gió xuân
        return `
        <svg viewBox="0 0 40 40" width="${size}" height="${size}" style="filter: drop-shadow(0 2px 3px rgba(243, 156, 18, 0.3));">
            <defs>
                <linearGradient id="maiPetalGrad_${id}" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#fffde7" />
                    <stop offset="40%" stop-color="#ffd700" />
                    <stop offset="100%" stop-color="#ff9800" />
                </linearGradient>
            </defs>
            <path d="M20,2 C27,9 36,15 35,26 C34,34 26,38 20,38 C14,38 6,34 5,26 C4,15 13,9 20,2 Z" 
                  fill="url(#maiPetalGrad_${id})" />
            <!-- Gân cánh hoa mai -->
            <path d="M20,10 Q20,25 20,33" stroke="rgba(214, 48, 49, 0.35)" stroke-width="1.5" stroke-linecap="round" fill="none" />
            <!-- Chân cánh phớt cam đỏ -->
            <ellipse cx="20" cy="35" rx="3" ry="2" fill="#d63031" opacity="0.6" />
        </svg>`;
    }

    // Nhành hoa mai vàng góc trên bên trái
    function getMaiVangBranchLeft() {
        return `
        <div class="spring-branch-left">
            <svg viewBox="0 0 250 200" width="250" height="200">
                <!-- Cành mai uốn lượn phong cách bonsai Tết -->
                <path d="M-10,-5 Q65,35 115,45 T210,65 M75,36 Q95,80 135,90 M125,48 Q155,25 190,20" 
                      stroke="#4e342e" stroke-width="5" stroke-linecap="round" fill="none" />
                <path d="M-10,-5 Q65,35 115,45 T210,65" stroke="#795548" stroke-width="2.5" fill="none" />
                
                <!-- Bông hoa mai vàng 5 cánh 1 -->
                <g transform="translate(115, 45)">
                    <circle cx="-8" cy="-8" r="9.5" fill="#ffd700" />
                    <circle cx="8" cy="-8" r="9.5" fill="#ffd700" />
                    <circle cx="10" cy="6" r="9.5" fill="#ffd700" />
                    <circle cx="-10" cy="6" r="9.5" fill="#ffd700" />
                    <circle cx="0" cy="11" r="9.5" fill="#ffd700" />
                    <!-- Nhụy đỏ cam Tết -->
                    <circle cx="0" cy="0" r="4.5" fill="#d63031" />
                    <circle cx="0" cy="0" r="2.5" fill="#f39c12" />
                    <circle cx="-2" cy="-2" r="1" fill="#fff" />
                </g>

                <!-- Bông hoa mai vàng 2 -->
                <g transform="translate(205, 65) scale(0.85)">
                    <circle cx="-8" cy="-8" r="9.5" fill="#ffca28" />
                    <circle cx="8" cy="-8" r="9.5" fill="#ffca28" />
                    <circle cx="10" cy="6" r="9.5" fill="#ffca28" />
                    <circle cx="-10" cy="6" r="9.5" fill="#ffca28" />
                    <circle cx="0" cy="11" r="9.5" fill="#ffca28" />
                    <circle cx="0" cy="0" r="4.5" fill="#d63031" />
                    <circle cx="0" cy="0" r="2" fill="#ffd700" />
                </g>

                <!-- Bông hoa mai vàng 3 -->
                <g transform="translate(135, 90) scale(0.9)">
                    <circle cx="-8" cy="-8" r="9.5" fill="#ffd700" />
                    <circle cx="8" cy="-8" r="9.5" fill="#ffd700" />
                    <circle cx="10" cy="6" r="9.5" fill="#ffd700" />
                    <circle cx="-10" cy="6" r="9.5" fill="#ffd700" />
                    <circle cx="0" cy="11" r="9.5" fill="#ffd700" />
                    <circle cx="0" cy="0" r="4.5" fill="#d63031" />
                    <circle cx="0" cy="0" r="2.5" fill="#f39c12" />
                </g>

                <!-- Bông hoa mai vàng 4 -->
                <g transform="translate(185, 20) scale(0.75)">
                    <circle cx="-8" cy="-8" r="9.5" fill="#ffc107" />
                    <circle cx="8" cy="-8" r="9.5" fill="#ffc107" />
                    <circle cx="10" cy="6" r="9.5" fill="#ffc107" />
                    <circle cx="-10" cy="6" r="9.5" fill="#ffc107" />
                    <circle cx="0" cy="11" r="9.5" fill="#ffc107" />
                    <circle cx="0" cy="0" r="4" fill="#d63031" />
                </g>

                <!-- Nụ hoa mai vàng & chồi non xanh biếc -->
                <circle cx="55" cy="25" r="5" fill="#ffd700" stroke="#f39c12" stroke-width="1" />
                <circle cx="95" cy="60" r="5.5" fill="#ffc107" stroke="#e67e22" stroke-width="1" />
                <circle cx="160" cy="52" r="5" fill="#ffd700" stroke="#f39c12" stroke-width="1" />
                <ellipse cx="68" cy="38" rx="6" ry="2.5" fill="#2ed573" transform="rotate(35, 68, 38)" />
                <ellipse cx="130" cy="54" rx="6" ry="2.5" fill="#2ed573" transform="rotate(-25, 130, 54)" />

                <!-- Dải nơ tài lộc đỏ may mắn treo đầu cành mai -->
                <g transform="translate(85, 40) scale(0.8)">
                    <path d="M0,0 Q-8,-6 -14,0 Q-8,6 0,0 Q8,-6 14,0 Q8,6 0,0" fill="#d63031" />
                    <circle cx="0" cy="0" r="3" fill="#ffd700" />
                    <line x1="-3" y1="2" x2="-6" y2="16" stroke="#d63031" stroke-width="2" />
                    <line x1="3" y1="2" x2="6" y2="16" stroke="#d63031" stroke-width="2" />
                </g>
            </svg>
        </div>`;
    }

    // Nhành hoa mai vàng góc trên bên phải
    function getMaiVangBranchRight() {
        return `
        <div class="spring-branch-right">
            <svg viewBox="0 0 230 190" width="230" height="190">
                <path d="M240,-5 Q170,38 118,48 T25,75 M160,42 Q138,85 95,95" 
                      stroke="#4e342e" stroke-width="4.5" stroke-linecap="round" fill="none" />
                <!-- Bông mai 1 -->
                <g transform="translate(118, 48) scale(0.9)">
                    <circle cx="-8" cy="-8" r="9.5" fill="#ffd700" />
                    <circle cx="8" cy="-8" r="9.5" fill="#ffd700" />
                    <circle cx="10" cy="6" r="9.5" fill="#ffd700" />
                    <circle cx="-10" cy="6" r="9.5" fill="#ffd700" />
                    <circle cx="0" cy="11" r="9.5" fill="#ffd700" />
                    <circle cx="0" cy="0" r="4.5" fill="#d63031" />
                    <circle cx="0" cy="0" r="2.5" fill="#f39c12" />
                </g>
                <!-- Bông mai 2 -->
                <g transform="translate(30, 75) scale(0.8)">
                    <circle cx="-8" cy="-8" r="9.5" fill="#ffca28" />
                    <circle cx="8" cy="-8" r="9.5" fill="#ffca28" />
                    <circle cx="10" cy="6" r="9.5" fill="#ffca28" />
                    <circle cx="-10" cy="6" r="9.5" fill="#ffca28" />
                    <circle cx="0" cy="11" r="9.5" fill="#ffca28" />
                    <circle cx="0" cy="0" r="4" fill="#d63031" />
                </g>
                <circle cx="95" cy="95" r="5" fill="#ffd700" />
                <circle cx="170" cy="28" r="4.5" fill="#ffc107" />
                <ellipse cx="140" cy="50" rx="5" ry="2" fill="#2ed573" transform="rotate(-30, 140, 50)" />
            </svg>
        </div>`;
    }

    // Chú Lân và Ông Địa chúc Tết đầu xuân
    function getLanVaOngDia() {
        return `
        <div class="spring-lan-ongdia" title="Chú Lân & Ông Địa Chúc Mừng Năm Mới - Tài Lộc Đầy Nhà!">
            <svg viewBox="0 0 260 170" width="220" height="155">
                <!-- ================= ÔNG ĐỊA ================= -->
                <g class="ong-dia-group" style="animation: ongDiaBounce 2.4s ease-in-out infinite;">
                    <!-- Bóng đổ -->
                    <ellipse cx="65" cy="155" rx="35" ry="8" fill="rgba(0,0,0,0.15)" />

                    <!-- Chân xếp bằng -->
                    <path d="M35,145 Q65,160 95,145 Q80,135 65,138 Q50,135 35,145 Z" fill="#b71540" />

                    <!-- Thân & Bụng bự phệ tròn phúc hậu -->
                    <ellipse cx="65" cy="115" rx="28" ry="24" fill="#ffe0b2" />
                    <!-- Rốn tròn xinh xắn -->
                    <circle cx="65" cy="120" r="2.5" fill="#f8c291" />

                    <!-- Áo bà ba đỏ khoác ngoài để phanh ngực hở bụng -->
                    <path d="M40,92 Q32,115 38,138 Q48,135 48,125 Q45,105 48,92 Z" fill="#d70018" stroke="#ffd700" stroke-width="1.5" />
                    <path d="M90,92 Q98,115 92,138 Q82,135 82,125 Q85,105 82,92 Z" fill="#d70018" stroke="#ffd700" stroke-width="1.5" />

                    <!-- Tay trái nâng thỏi vàng Kim Nguyên Bảo -->
                    <path d="M85,100 Q105,105 102,118 Q92,122 85,115" stroke="#ffe0b2" stroke-width="9" stroke-linecap="round" fill="none" />
                    <g transform="translate(98, 106) scale(0.7)">
                        <path d="M2,12 Q14,2 26,12 Q20,20 8,20 Z" fill="#ffd700" stroke="#f39c12" stroke-width="1" />
                        <ellipse cx="14" cy="9" rx="8" ry="4" fill="#fff9c4" />
                    </g>

                    <!-- Cổ & Đầu tròn trĩnh, nụ cười hiền từ -->
                    <circle cx="65" cy="68" r="24" fill="#ffe0b2" />
                    <!-- 2 tai dài phúc tướng -->
                    <ellipse cx="39" cy="72" rx="4.5" ry="11" fill="#ffe0b2" />
                    <ellipse cx="91" cy="72" rx="4.5" ry="11" fill="#ffe0b2" />

                    <!-- Má hồng đào -->
                    <circle cx="48" cy="74" r="5" fill="#ff7675" opacity="0.6" />
                    <circle cx="82" cy="74" r="5" fill="#ff7675" opacity="0.6" />
                    <!-- Mắt híp cười tít mắt -->
                    <path d="M47,65 Q54,58 60,65" stroke="#2d3436" stroke-width="2.5" stroke-linecap="round" fill="none" />
                    <path d="M70,65 Q76,58 83,65" stroke="#2d3436" stroke-width="2.5" stroke-linecap="round" fill="none" />
                    <!-- Mũi tròn -->
                    <ellipse cx="65" cy="70" rx="3.5" ry="2.5" fill="#f8c291" />
                    <!-- Miệng cười toét toát thấy răng rộn rã -->
                    <path d="M52,78 Q65,95 78,78 Z" fill="#d63031" />
                    <path d="M57,78 Q65,84 73,78" fill="#ffffff" />
                    <!-- Dải ruy băng đỏ thắt trên đầu -->
                    <path d="M45,50 Q65,42 85,50" stroke="#d70018" stroke-width="4" stroke-linecap="round" fill="none" />
                    <circle cx="65" cy="45" r="4" fill="#ffd700" />

                    <!-- Cánh tay phải cầm quạt mo cau phe phẩy -->
                    <g style="animation: ongDiaFan 2s ease-in-out infinite; transform-origin: 45px 105px;">
                        <path d="M45,102 Q28,105 24,95" stroke="#ffe0b2" stroke-width="8" stroke-linecap="round" fill="none" />
                        <!-- Quạt mo cau -->
                        <path d="M22,96 Q10,75 5,55 Q18,48 30,62 Q35,80 22,96 Z" fill="#d4ac0d" stroke="#b7950b" stroke-width="1.5" />
                        <line x1="22" y1="96" x2="16" y2="58" stroke="#7d6608" stroke-width="2" />
                        <line x1="16" y1="58" x2="10" y2="65" stroke="#7d6608" stroke-width="1.5" />
                        <line x1="16" y1="58" x2="24" y2="70" stroke="#7d6608" stroke-width="1.5" />
                    </g>
                </g>

                <!-- ================= CHÚ LÂN TẾT ================= -->
                <g class="chu-lan-group" style="animation: lanHeadDance 3s ease-in-out infinite; transform-origin: 190px 145px;">
                    <!-- Bóng đổ -->
                    <ellipse cx="190" cy="155" rx="45" ry="9" fill="rgba(0,0,0,0.15)" />

                    <!-- Thân Lân phủ gấm đỏ vảy rồng vàng -->
                    <path d="M165,115 Q210,105 245,125 Q240,150 205,148 Q170,148 165,115 Z" fill="#d70018" stroke="#ffd700" stroke-width="2" />
                    <path d="M190,125 Q198,135 206,125" stroke="#ffd700" stroke-width="2" fill="none" />
                    <path d="M205,130 Q213,140 221,130" stroke="#ffd700" stroke-width="2" fill="none" />
                    <path d="M178,132 Q186,142 194,132" stroke="#ffd700" stroke-width="2" fill="none" />
                    <!-- Đuôi Lân lông xù vàng trắng -->
                    <ellipse cx="248" cy="125" rx="8" ry="12" fill="#ffd700" transform="rotate(30, 248, 125)" />
                    <circle cx="253" cy="120" r="5" fill="#ffffff" />

                    <!-- Chân Lân nhún nhảy -->
                    <ellipse cx="178" cy="150" rx="10" ry="6" fill="#ffd700" />
                    <ellipse cx="215" cy="150" rx="10" ry="6" fill="#ffd700" />

                    <!-- ĐẦU LÂN RỰC RỠ SẮC ĐỎ - VÀNG -->
                    <g transform="translate(140, 45)">
                        <!-- Bờm lông vũ trắng sau đầu -->
                        <circle cx="28" cy="40" r="26" fill="#ffffff" />
                        <circle cx="48" cy="40" r="26" fill="#ffffff" />

                        <!-- Khuôn mặt đầu Lân đỏ rực -->
                        <path d="M10,25 Q38,5 68,25 Q78,55 60,75 Q38,82 18,75 Q0,55 10,25 Z" fill="#d70018" stroke="#f39c12" stroke-width="2" />

                        <!-- Sừng Lân đỉnh đỏ may mắn -->
                        <path d="M38,2 L32,18 L44,18 Z" fill="#f1c40f" stroke="#d63031" stroke-width="1.5" />
                        <circle cx="38" cy="2" r="4.5" fill="#d70018" />

                        <!-- Gương bát quái / Ngọc tròn giữa trán -->
                        <circle cx="38" cy="22" r="7" fill="#00cec9" stroke="#ffd700" stroke-width="2" />
                        <circle cx="38" cy="22" r="3" fill="#ffffff" />

                        <!-- 2 Mắt Lân to tròn chớp nháy -->
                        <g style="animation: lanBlinkEye 3.5s infinite; transform-origin: 38px 38px;">
                            <circle cx="24" cy="38" r="9" fill="#ffffff" stroke="#2d3436" stroke-width="1.5" />
                            <circle cx="25" cy="38" r="5.5" fill="#2d3436" />
                            <circle cx="23" cy="36" r="2" fill="#ffffff" />
                            <path d="M14,29 Q24,24 33,30" stroke="#f39c12" stroke-width="3" fill="none" />

                            <circle cx="52" cy="38" r="9" fill="#ffffff" stroke="#2d3436" stroke-width="1.5" />
                            <circle cx="51" cy="38" r="5.5" fill="#2d3436" />
                            <circle cx="53" cy="36" r="2" fill="#ffffff" />
                            <path d="M43,30 Q52,24 62,29" stroke="#f39c12" stroke-width="3" fill="none" />
                        </g>

                        <!-- Mũi Lân phồng to viền vàng -->
                        <ellipse cx="38" cy="50" rx="9" ry="6" fill="#ffd700" />
                        <circle cx="34" cy="50" r="2" fill="#d63031" />
                        <circle cx="42" cy="50" r="2" fill="#d63031" />

                        <!-- Miệng Lân há rộng phước lộc -->
                        <path d="M16,56 Q38,72 60,56 Q52,80 38,80 Q24,80 16,56 Z" fill="#2d3436" />
                        <!-- Lưỡi đỏ Lân lè ra tinh nghịch -->
                        <path d="M30,68 Q38,82 46,68 Z" fill="#ff4757" />
                        <polygon points="22,57 26,64 28,58" fill="#ffffff" />
                        <polygon points="54,57 50,64 48,58" fill="#ffffff" />

                        <!-- Chòm râu Lân trắng xù cằm -->
                        <path d="M25,78 Q38,98 51,78 Z" fill="#ffffff" />
                    </g>
                </g>
            </svg>
        </div>`;
    }

    // =========================================================================
    // 2. MÙA HẠ: BÃI BIỂN, CÂY DỪA, SÓNG BIỂN, MẶT TRỜI
    // =========================================================================
    function getSummerPalmTree() {
        return `
        <div class="summer-palm-tree">
            <svg viewBox="0 0 250 320" width="250" height="320">
                <path d="M220,320 Q200,200 130,120" stroke="#795548" stroke-width="18" stroke-linecap="round" fill="none" />
                <path d="M220,320 Q200,200 130,120" stroke="#a1887f" stroke-width="12" stroke-linecap="round" fill="none" />
                <line x1="195" y1="260" x2="215" y2="265" stroke="#4e342e" stroke-width="3" />
                <line x1="180" y1="220" x2="198" y2="225" stroke="#4e342e" stroke-width="3" />
                <line x1="160" y1="180" x2="178" y2="185" stroke="#4e342e" stroke-width="3" />
                <line x1="140" y1="145" x2="155" y2="150" stroke="#4e342e" stroke-width="3" />
                
                <circle cx="132" cy="128" r="9" fill="#5d4037" />
                <circle cx="145" cy="132" r="9.5" fill="#4e342e" />
                <circle cx="138" cy="142" r="8.5" fill="#6d4c41" />

                <path d="M130,120 Q50,90 5,140" stroke="#27ae60" stroke-width="8" stroke-linecap="round" fill="none" />
                <path d="M130,120 Q60,60 20,85" stroke="#2ecc71" stroke-width="7" stroke-linecap="round" fill="none" />
                <path d="M130,120 Q120,40 100,10" stroke="#2ecc71" stroke-width="7" stroke-linecap="round" fill="none" />
                <path d="M130,120 Q160,30 180,15" stroke="#1abc9c" stroke-width="7" stroke-linecap="round" fill="none" />
                <path d="M130,120 Q200,80 245,110" stroke="#27ae60" stroke-width="8" stroke-linecap="round" fill="none" />
                <path d="M130,120 Q210,120 240,165" stroke="#2ecc71" stroke-width="7" stroke-linecap="round" fill="none" />
                <path d="M130,120 Q100,120 70,170" stroke="#16a085" stroke-width="6" stroke-linecap="round" fill="none" />
            </svg>
        </div>`;
    }

    function getSummerBeachFloor() {
        return `
        <div class="summer-beach-floor">
            <svg class="summer-wave-layer" viewBox="0 0 1200 40" preserveAspectRatio="none">
                <path d="M0,20 C150,35 350,5 500,20 C650,35 850,5 1000,20 C1150,35 1200,20 1200,20 L1200,40 L0,40 Z"></path>
            </svg>
            <svg class="summer-wave-layer wave-front" viewBox="0 0 1200 40" preserveAspectRatio="none">
                <path d="M0,15 C200,0 400,30 600,15 C800,0 1000,30 1200,15 L1200,40 L0,40 Z"></path>
            </svg>
            <div class="summer-sand-strip"></div>
            <div class="summer-umbrella">
                <svg viewBox="0 0 50 50" width="48" height="48">
                    <line x1="25" y1="15" x2="25" y2="48" stroke="#795548" stroke-width="2.5" />
                    <path d="M5,22 Q25,2 45,22 Z" fill="#d63031" />
                    <path d="M12,22 Q25,5 25,22 Q25,5 38,22 Z" fill="#ffffff" />
                    <circle cx="25" cy="5" r="2" fill="#ffd700" />
                </svg>
            </div>
            <div class="summer-starfish">
                <svg viewBox="0 0 24 24" width="18" height="18">
                    <path d="M12,2 L14.5,8.5 L21.5,9.5 L16.5,14.5 L18,21.5 L12,18 L6,21.5 L7.5,14.5 L2.5,9.5 L9.5,8.5 Z" fill="#ff7675" stroke="#d63031" stroke-width="1" />
                </svg>
            </div>
            <div class="summer-surfboard">
                <svg viewBox="0 0 24 70" width="22" height="60">
                    <ellipse cx="12" cy="35" rx="10" ry="32" fill="#0984e3" />
                    <path d="M7,15 Q12,5 17,15 L17,55 Q12,65 7,55 Z" fill="#ffd32a" />
                    <line x1="12" y1="5" x2="12" y2="65" stroke="#ffffff" stroke-width="2" />
                </svg>
            </div>
        </div>`;
    }

    function getSummerSun() {
        return `
        <div class="summer-sun">
            <div class="summer-sun-glow"></div>
            <div class="summer-sun-rays">
                <svg viewBox="0 0 100 100" width="90" height="90">
                    <circle cx="50" cy="50" r="22" fill="#ff9f43" />
                    <circle cx="50" cy="50" r="18" fill="#ffd32a" />
                    <line x1="50" y1="5" x2="50" y2="18" stroke="#ff9f43" stroke-width="4" stroke-linecap="round" />
                    <line x1="50" y1="82" x2="50" y2="95" stroke="#ff9f43" stroke-width="4" stroke-linecap="round" />
                    <line x1="5" y1="50" x2="18" y2="50" stroke="#ff9f43" stroke-width="4" stroke-linecap="round" />
                    <line x1="82" y1="50" x2="95" y2="50" stroke="#ff9f43" stroke-width="4" stroke-linecap="round" />
                    <line x1="18" y1="18" x2="28" y2="28" stroke="#ffd32a" stroke-width="3.5" stroke-linecap="round" />
                    <line x1="72" y1="72" x2="82" y2="82" stroke="#ffd32a" stroke-width="3.5" stroke-linecap="round" />
                    <line x1="18" y1="82" x2="28" y2="72" stroke="#ffd32a" stroke-width="3.5" stroke-linecap="round" />
                    <line x1="72" y1="28" x2="82" y2="18" stroke="#ffd32a" stroke-width="3.5" stroke-linecap="round" />
                </svg>
            </div>
        </div>
        <div class="summer-seagull">
            <svg viewBox="0 0 40 20" width="40" height="20">
                <path d="M2,14 Q10,2 20,10 Q30,2 38,14 Q28,8 20,12 Q12,8 2,14 Z" fill="#34495e" />
            </svg>
        </div>
        <div class="summer-seagull">
            <svg viewBox="0 0 40 20" width="40" height="20">
                <path d="M2,14 Q10,2 20,10 Q30,2 38,14 Q28,8 20,12 Q12,8 2,14 Z" fill="#2c3e50" />
            </svg>
        </div>`;
    }

    // =========================================================================
    // 3. MÙA THU: LÁ PHONG RƠI & NHÁNH PHONG
    // =========================================================================
    function getMapleLeafSvg(color, size) {
        return `
        <svg viewBox="0 0 100 100" width="${size}" height="${size}" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));">
            <path d="M50,2 L53,24 L63,14 L65,27 L78,21 L75,36 L89,35 L81,48 L94,52 L82,62 L89,72 L73,70 L72,86 L59,75 L53,96 L47,96 L41,75 L28,86 L27,70 L11,72 L18,62 L6,52 L19,48 L11,35 L25,36 L22,21 L35,27 L37,14 L47,24 Z" 
                  fill="${color}" />
            <path d="M50,96 L50,15 M50,45 L70,30 M50,45 L30,30 M50,65 L75,55 M50,65 L25,55" 
                  stroke="rgba(255,255,255,0.3)" stroke-width="2" stroke-linecap="round" fill="none" />
        </svg>`;
    }

    function getAutumnBranch() {
        return `
        <div class="autumn-branch-left">
            <svg viewBox="0 0 250 200" width="250" height="200">
                <path d="M-10,-5 Q70,40 120,50 T220,80 M80,42 Q105,95 145,110 M140,55 Q175,30 205,25" 
                      stroke="#5d4037" stroke-width="4.5" stroke-linecap="round" fill="none" />
                <g transform="translate(120, 50) rotate(-15) scale(0.65)">
                    ${getMapleLeafSvg('#c0392b', 70)}
                </g>
                <g transform="translate(200, 75) rotate(25) scale(0.6)">
                    ${getMapleLeafSvg('#e67e22', 70)}
                </g>
                <g transform="translate(145, 105) rotate(10) scale(0.55)">
                    ${getMapleLeafSvg('#f39c12', 70)}
                </g>
                <g transform="translate(195, 20) rotate(-30) scale(0.5)">
                    ${getMapleLeafSvg('#d35400', 70)}
                </g>
            </svg>
        </div>`;
    }

    // =========================================================================
    // 4. MÙA ĐÔNG: TUYẾT RƠI, ÔNG GIÀ NOEL & TUẦN LỘC
    // =========================================================================
    function getWinterSantaSleigh() {
        return `
        <div class="winter-santa-sleigh" title="Merry Christmas!">
            <div class="winter-magic-trail">
                <div class="winter-magic-star" style="right: 15px; top: 12px; animation-delay: 0.1s;"></div>
                <div class="winter-magic-star" style="right: 35px; top: 5px; animation-delay: 0.3s; width: 8px; height: 8px;"></div>
                <div class="winter-magic-star" style="right: 60px; top: 18px; animation-delay: 0.5s;"></div>
                <div class="winter-magic-star" style="right: 90px; top: 8px; animation-delay: 0.2s; width: 7px; height: 7px;"></div>
                <div class="winter-magic-star" style="right: 120px; top: 14px; animation-delay: 0.6s;"></div>
            </div>

            <svg viewBox="0 0 320 110" width="270" height="95">
                <path d="M125,58 Q170,52 205,62" stroke="#f1c40f" stroke-width="2" stroke-dasharray="3,2" fill="none" />
                <path d="M60,48 Q100,50 135,55" stroke="#f1c40f" stroke-width="2" stroke-dasharray="3,2" fill="none" />

                <!-- TUẦN LỘC 1 -->
                <g transform="translate(10, 20) scale(0.65)">
                    <ellipse cx="65" cy="55" rx="30" ry="16" fill="#8d6e63" />
                    <path d="M80,50 Q95,35 90,20 Q85,15 75,25 Z" fill="#8d6e63" />
                    <circle cx="92" cy="18" r="4.5" fill="#e74c3c" filter="drop-shadow(0 0 4px #ff0000)" />
                    <circle cx="84" cy="18" r="2" fill="#212121" />
                    <ellipse cx="76" cy="15" rx="3" ry="7" fill="#6d4c41" transform="rotate(-30, 76, 15)" />
                    <path d="M78,14 Q80,2 75,-8 M76,-2 L85,-5 M77,-5 L70,-10" stroke="#f1c40f" stroke-width="3" stroke-linecap="round" fill="none" />
                    <path d="M78,35 Q84,42 88,40" stroke="#c0392b" stroke-width="4" stroke-linecap="round" />
                    <circle cx="83" cy="41" r="3" fill="#f1c40f" />
                    <path d="M45,60 Q30,75 20,85 M55,62 Q50,78 45,92 M80,60 Q95,75 110,80 M90,60 Q105,72 118,85" 
                          stroke="#6d4c41" stroke-width="4.5" stroke-linecap="round" />
                    <path d="M36,52 Q30,48 32,45" stroke="#8d6e63" stroke-width="4" stroke-linecap="round" />
                </g>

                <!-- TUẦN LỘC 2 -->
                <g transform="translate(80, 28) scale(0.6)">
                    <ellipse cx="65" cy="55" rx="30" ry="16" fill="#795548" />
                    <path d="M80,50 Q95,35 90,20 Q85,15 75,25 Z" fill="#795548" />
                    <circle cx="92" cy="18" r="3.5" fill="#e74c3c" />
                    <circle cx="84" cy="18" r="2" fill="#212121" />
                    <path d="M78,14 Q80,2 75,-8 M76,-2 L85,-5" stroke="#f1c40f" stroke-width="3" stroke-linecap="round" fill="none" />
                    <path d="M78,35 Q84,42 88,40" stroke="#c0392b" stroke-width="4" stroke-linecap="round" />
                    <circle cx="83" cy="41" r="3" fill="#f1c40f" />
                    <path d="M45,60 Q30,75 22,85 M55,62 Q50,78 48,92 M80,60 Q95,75 105,80 M90,60 Q105,72 115,85" 
                          stroke="#5d4037" stroke-width="4.5" stroke-linecap="round" />
                </g>

                <!-- CỖ XE VÀ ÔNG GIÀ NOEL -->
                <g transform="translate(185, 20) scale(0.72)">
                    <polygon points="40,25 25,50 55,50" fill="#27ae60" />
                    <polygon points="40,15 28,35 52,35" fill="#2ecc71" />
                    <circle cx="40" cy="14" r="3.5" fill="#f1c40f" />
                    <circle cx="34" cy="32" r="2" fill="#e74c3c" />
                    <circle cx="46" cy="42" r="2" fill="#f39c12" />

                    <rect x="48" y="38" width="16" height="15" fill="#e74c3c" rx="2" />
                    <line x1="56" y1="38" x2="56" y2="53" stroke="#f1c40f" stroke-width="2" />
                    <line x1="48" y1="45.5" x2="64" y2="45.5" stroke="#f1c40f" stroke-width="2" />
                    
                    <rect x="62" y="32" width="14" height="20" fill="#3498db" rx="2" />
                    <line x1="69" y1="32" x2="69" y2="52" stroke="#ffffff" stroke-width="2" />

                    <path d="M25,52 Q40,65 95,65 Q115,65 125,50 Q110,48 95,50 L35,50 Z" fill="#c0392b" stroke="#f1c40f" stroke-width="2" />
                    
                    <path d="M15,74 L115,74 Q135,74 138,58" stroke="#f1c40f" stroke-width="4" stroke-linecap="round" fill="none" />
                    <line x1="40" y1="65" x2="35" y2="74" stroke="#d4ac0d" stroke-width="3" />
                    <line x1="90" y1="65" x2="85" y2="74" stroke="#d4ac0d" stroke-width="3" />

                    <ellipse cx="85" cy="38" rx="16" ry="14" fill="#c0392b" />
                    <rect x="74" y="38" width="22" height="4" fill="#212121" />
                    <rect x="83" y="37" width="5" height="6" fill="#f1c40f" />
                    <path d="M80,24 Q90,38 98,24 Q95,36 88,40 Q82,36 80,24 Z" fill="#ffffff" />
                    <circle cx="89" cy="20" r="8" fill="#ffccbc" />
                    <circle cx="94" cy="20" r="2" fill="#c0392b" />
                    <circle cx="91" cy="18" r="1.5" fill="#212121" />
                    <path d="M82,15 Q90,6 102,12 L85,6 Z" fill="#c0392b" />
                    <rect x="80" y="13" width="18" height="4" fill="#ffffff" rx="2" />
                    <circle cx="104" cy="14" r="3.5" fill="#ffffff" />
                    <path d="M96,32 Q108,24 105,15" stroke="#c0392b" stroke-width="5" stroke-linecap="round" fill="none" />
                    <circle cx="105" cy="14" r="3.5" fill="#ffffff" />
                </g>
            </svg>
        </div>`;
    }

    function getWinterIcicles() {
        return `
        <div class="winter-icicles-banner">
            <svg viewBox="0 0 1200 24" width="100%" height="20" preserveAspectRatio="none">
                <path d="M0,0 L1200,0 L1200,8 Q1150,22 1120,6 Q1080,24 1040,7 Q990,20 960,6 Q900,24 850,7 Q800,22 760,6 Q700,24 640,7 Q580,20 540,6 Q480,24 420,8 Q360,22 320,6 Q260,24 200,7 Q140,20 100,6 Q50,22 0,8 Z" 
                      fill="rgba(255, 255, 255, 0.9)" />
            </svg>
        </div>`;
    }

    // =========================================================================
    // VẼ HIỆU ỨNG TƯƠNG ỨNG MÙA ĐƯỢC CHỌN
    // =========================================================================
    function renderSeason(season, targetEl) {
        if (!targetEl) return;
        targetEl.innerHTML = '';

        if (!isEffectsEnabled || season === 'default') {
            return;
        }

        // 1. MÙA XUÂN (Spring): Cánh hoa mai vàng rơi, Cành mai vàng & Chú Lân - Ông Địa (Tone Đỏ Tết)
        if (season === 'theme-spring') {
            targetEl.insertAdjacentHTML('beforeend', getMaiVangBranchLeft());
            targetEl.insertAdjacentHTML('beforeend', getMaiVangBranchRight());
            targetEl.insertAdjacentHTML('beforeend', getLanVaOngDia());

            // Sinh 28 cánh hoa mai vàng rơi chao liệng
            const petalCount = window.innerWidth < 768 ? 14 : 28;
            for (let i = 0; i < petalCount; i++) {
                const petal = document.createElement('div');
                petal.className = 'spring-petal';
                const size = Math.floor(Math.random() * 16) + 16; // 16px - 32px
                const left = Math.random() * 100;
                const durationFall = (Math.random() * 6 + 7).toFixed(2);
                const durationSway = (Math.random() * 3 + 3).toFixed(2);
                const delay = (Math.random() * 8).toFixed(2);
                const opacity = (Math.random() * 0.3 + 0.7).toFixed(2);

                petal.style.left = `${left}%`;
                petal.style.opacity = opacity;
                petal.style.animationDuration = `${durationFall}s, ${durationSway}s`;
                petal.style.animationDelay = `${delay}s, ${delay}s`;
                petal.innerHTML = getMaiVangPetalSvg(size, i);
                targetEl.appendChild(petal);
            }
        }

        // 2. MÙA HẠ (Summer): Bãi biển, cây dừa, sóng biển, mặt trời
        else if (season === 'theme-summer') {
            targetEl.insertAdjacentHTML('beforeend', getSummerPalmTree());
            targetEl.insertAdjacentHTML('beforeend', getSummerBeachFloor());
            targetEl.insertAdjacentHTML('beforeend', getSummerSun());
        }

        // 3. MÙA THU (Autumn): Lá phong rơi & Nhánh lá phong vàng đỏ
        else if (season === 'theme-autumn') {
            targetEl.insertAdjacentHTML('beforeend', getAutumnBranch());

            const leafCount = window.innerWidth < 768 ? 14 : 26;
            for (let i = 0; i < leafCount; i++) {
                const leaf = document.createElement('div');
                leaf.className = 'autumn-maple-leaf';
                const size = Math.floor(Math.random() * 16) + 20;
                const left = Math.random() * 100;
                const color = mapleColors[Math.floor(Math.random() * mapleColors.length)];
                const durationFall = (Math.random() * 7 + 8).toFixed(2);
                const durationTumble = (Math.random() * 4 + 3.5).toFixed(2);
                const delay = (Math.random() * 9).toFixed(2);
                const opacity = (Math.random() * 0.35 + 0.65).toFixed(2);

                leaf.style.left = `${left}%`;
                leaf.style.opacity = opacity;
                leaf.style.animationDuration = `${durationFall}s, ${durationTumble}s`;
                leaf.style.animationDelay = `${delay}s, ${delay}s`;
                leaf.innerHTML = getMapleLeafSvg(color, size);
                targetEl.appendChild(leaf);
            }
        }

        // 4. MÙA ĐÔNG (Winter): Tuyết rơi, Ông già Noel & Tuần Lộc
        else if (season === 'theme-winter') {
            targetEl.insertAdjacentHTML('beforeend', getWinterIcicles());
            targetEl.insertAdjacentHTML('beforeend', getWinterSantaSleigh());

            const snowIcons = ['❄', '❅', '❆', '•'];
            const snowCount = window.innerWidth < 768 ? 20 : 38;
            for (let i = 0; i < snowCount; i++) {
                const snow = document.createElement('div');
                snow.className = 'winter-snowflake';
                const icon = snowIcons[Math.floor(Math.random() * snowIcons.length)];
                const size = Math.floor(Math.random() * 16) + 12;
                const left = Math.random() * 100;
                const durationFall = (Math.random() * 6 + 6).toFixed(2);
                const durationDrift = (Math.random() * 3 + 3).toFixed(2);
                const delay = (Math.random() * 8).toFixed(2);
                const opacity = (Math.random() * 0.4 + 0.6).toFixed(2);

                snow.style.left = `${left}%`;
                snow.style.fontSize = `${size}px`;
                snow.style.opacity = opacity;
                snow.style.animationDuration = `${durationFall}s, ${durationDrift}s`;
                snow.style.animationDelay = `${delay}s, ${delay}s`;
                snow.textContent = icon;
                targetEl.appendChild(snow);
            }
        }
    }

    // Nhận diện mùa trực tiếp từ class trên thẻ body (được nạp từ theme.json của Admin)
    function detectSeasonFromDOM() {
        const body = document.body;
        if (body.classList.contains('theme-spring')) return 'theme-spring';
        if (body.classList.contains('theme-summer')) return 'theme-summer';
        if (body.classList.contains('theme-autumn')) return 'theme-autumn';
        if (body.classList.contains('theme-winter')) return 'theme-winter';
        return 'default';
    }

    return {
        // Khởi động Engine: Tôn trọng hoàn toàn thiết lập của Admin, khách hàng không tự ý đổi
        init: function () {
            currentSeason = detectSeasonFromDOM();

            if (!containerEl) {
                containerEl = document.createElement('div');
                containerEl.className = 'season-effects-container';
                containerEl.id = 'seasonEffectsContainer';
                document.body.appendChild(containerEl);
            }

            renderSeason(currentSeason, containerEl);
        },

        // Dành riêng cho Quản trị viên (Admin) thay đổi mùa
        changeSeason: function (newSeason) {
            currentSeason = newSeason;

            document.body.classList.remove('theme-spring', 'theme-summer', 'theme-autumn', 'theme-winter');
            if (newSeason !== 'default') {
                document.body.classList.add(newSeason);
            }

            if (!containerEl) {
                containerEl = document.getElementById('seasonEffectsContainer');
            }
            renderSeason(currentSeason, containerEl);
        },

        // Bật / tắt hiệu ứng (dành cho Admin test)
        toggleEffects: function () {
            isEffectsEnabled = !isEffectsEnabled;
            if (!containerEl) {
                containerEl = document.getElementById('seasonEffectsContainer');
            }
            renderSeason(currentSeason, containerEl);
        },

        // Áp dụng trực tiếp vào khung Live Preview trong trang Quản Trị Admin [A9]
        applyToPreview: function (previewElement, season) {
            if (!previewElement) return;

            let prevContainer = previewElement.querySelector('.season-preview-overlay');
            if (!prevContainer) {
                prevContainer = document.createElement('div');
                prevContainer.className = 'season-preview-overlay';
                prevContainer.style.position = 'absolute';
                prevContainer.style.top = '0';
                prevContainer.style.left = '0';
                prevContainer.style.width = '100%';
                prevContainer.style.height = '100%';
                prevContainer.style.pointerEvents = 'none';
                prevContainer.style.overflow = 'hidden';
                prevContainer.style.zIndex = '5';
                previewElement.style.position = 'relative';
                previewElement.appendChild(prevContainer);
            }

            renderSeason(season, prevContainer);
        }
    };
})();

// Tự động kích hoạt khi trang tải xong
document.addEventListener('DOMContentLoaded', function () {
    SeasonEffects.init();
});
