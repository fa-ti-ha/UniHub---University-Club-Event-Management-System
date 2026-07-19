<?php
// Shared dashboard sidebar include for all roles
function renderDashboardShell(string $role, array $user, string $activeItem, string $pageTitle, PDO $pdo): void {
    $unread = getUnreadNotificationCount($pdo, $user['id']);
    $dashBase = BASE_URL . '/dashboard/' . str_replace('_', '-', $role);
    $navItems = [];
    if ($role === 'student') {
        $navItems = [
            ['href' => $dashBase . '/index.php',         'icon' => 'ri-dashboard-line',    'label' => 'Dashboard'],
            ['href' => $dashBase . '/my-clubs.php',      'icon' => 'ri-building-4-line',   'label' => 'My Clubs'],
            ['href' => $dashBase . '/my-events.php',     'icon' => 'ri-calendar-event-line','label' => 'My Events'],
            ['href' => $dashBase . '/notifications.php', 'icon' => 'ri-notification-3-line','label' => 'Notifications', 'badge' => $unread],
            ['href' => $dashBase . '/profile.php',       'icon' => 'ri-user-line',          'label' => 'Profile'],
            ['divider' => true],
            ['href' => BASE_URL . '/pages/clubs.php',    'icon' => 'ri-compass-line',       'label' => 'Browse Clubs'],
            ['href' => BASE_URL . '/pages/events.php',   'icon' => 'ri-ticket-line',        'label' => 'Browse Events'],
            ['href' => BASE_URL . '/pages/create-club.php','icon'=>'ri-add-circle-line',    'label' => 'Start a Club'],
        ];
    } elseif ($role === 'club_admin') {
        $navItems = [
            ['href' => $dashBase . '/index.php',         'icon' => 'ri-dashboard-line',    'label' => 'Dashboard'],
            ['href' => $dashBase . '/members.php',       'icon' => 'ri-group-line',        'label' => 'Members'],
            ['href' => $dashBase . '/requests.php',      'icon' => 'ri-user-add-line',     'label' => 'Join Requests', 'badge' => $unread],
            ['href' => $dashBase . '/events.php',        'icon' => 'ri-calendar-event-line','label' => 'Events'],
            ['href' => $dashBase . '/create-event.php',  'icon' => 'ri-add-circle-line',   'label' => 'Create Event'],
            ['href' => $dashBase . '/edit-club.php',     'icon' => 'ri-edit-line',         'label' => 'Edit Club'],
            ['divider' => true],
            ['href' => BASE_URL . '/pages/clubs.php',    'icon' => 'ri-compass-line',      'label' => 'All Clubs'],
        ];
    } else {
        // super_admin
        $navItems = [
            ['href' => $dashBase . '/index.php',          'icon' => 'ri-dashboard-line',    'label' => 'Dashboard'],
            ['href' => $dashBase . '/users.php',          'icon' => 'ri-group-line',        'label' => 'Manage Users'],
            ['href' => $dashBase . '/clubs.php',          'icon' => 'ri-building-4-line',   'label' => 'Manage Clubs'],
            ['href' => $dashBase . '/events.php',         'icon' => 'ri-calendar-event-line','label' => 'Manage Events'],
            ['href' => $dashBase . '/club-requests.php',  'icon' => 'ri-file-add-line',     'label' => 'Club Requests', 'badge' => $unread],
            ['href' => $dashBase . '/event-requests.php', 'icon' => 'ri-calendar-check-line','label' => 'Event Requests'],
            ['divider' => true],
            ['href' => BASE_URL . '/index.php',           'icon' => 'ri-home-line',         'label' => 'Public Site'],
        ];
    }
    ?>
    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebarOverlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99;display:none;opacity:0;transition:opacity 0.3s"></div>

    <aside class="sidebar" id="dashSidebar">
        <div class="sidebar-header">
            <!-- Brand -->
            <a href="<?= BASE_URL ?>/index.php" style="display:flex;align-items:center;gap:0.6rem;text-decoration:none;margin-bottom:1rem">
                <div style="width:32px;height:32px;background:linear-gradient(135deg,#1a56db,#7c3aed);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem"><i class="ri-building-4-line"></i></div>
                <span style="font-size:1.1rem;font-weight:800;color:#fff">Uni<strong>Hub</strong></span>
            </a>
            <div class="sidebar-user">
                <img src="<?= profilePicUrl($user['profile_picture']) ?>" alt="" class="sidebar-avatar" />
                <div>
                    <span class="sidebar-user-name"><?= htmlspecialchars($user['full_name']) ?></span>
                    <span class="sidebar-user-role"><?= ucfirst(str_replace('_',' ',$role)) ?></span>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $item): ?>
                <?php if (isset($item['divider'])): ?>
                <div style="height:1px;background:rgba(255,255,255,0.06);margin:0.75rem 1.5rem"></div>
                <?php else: ?>
                <a href="<?= $item['href'] ?>" class="sidebar-item <?= ($activeItem === $item['label']) ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i>
                    <?= $item['label'] ?>
                    <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                    <span class="sidebar-badge"><?= $item['badge'] > 9 ? '9+' : $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/api/auth.php?action=logout" class="sidebar-logout">
                <i class="ri-logout-box-r-line"></i> Logout
            </a>
        </div>
    </aside>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div style="display:flex;align-items:center;gap:1rem">
                <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="ri-menu-line"></i></button>
                <h1 class="dashboard-topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
            <div class="dashboard-topbar-actions">
                <button class="btn-icon" id="themeToggle2" onclick="document.getElementById('themeToggle')?.click()" title="Toggle theme"><i class="ri-moon-line"></i></button>
                <a href="<?= $dashBase ?>/notifications.php" class="btn-icon" style="position:relative" title="Notifications">
                    <i class="ri-notification-3-line"></i>
                    <?php if ($unread): ?><span style="position:absolute;top:0;right:0;background:var(--color-danger);color:#fff;font-size:10px;border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center;font-weight:700"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
                </a>
                <img src="<?= profilePicUrl($user['profile_picture']) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;cursor:pointer;border:2px solid var(--color-border)" title="<?= htmlspecialchars($user['full_name']) ?>" />
            </div>
        </div>
        <div class="dashboard-content">
    <?php
}

function renderDashboardEnd(): void {
    echo '</div></div>';
}

function toggleSidebarScript(): string {
    return '<script>
function toggleSidebar() {
    const s = document.getElementById("dashSidebar");
    const o = document.getElementById("sidebarOverlay");
    if (!s) return;
    s.classList.toggle("open");
    if (s.classList.contains("open")) { o.style.display="block"; setTimeout(()=>o.style.opacity="1",10); document.body.style.overflow="hidden"; }
    else { o.style.opacity="0"; setTimeout(()=>o.style.display="none",300); document.body.style.overflow=""; }
}
document.getElementById("sidebarOverlay")?.addEventListener("click", toggleSidebar);
// Theme icon sync for dashboard topbar button
const t2 = document.getElementById("themeToggle2");
if (t2) {
    const theme = document.documentElement.getAttribute("data-theme");
    t2.querySelector("i").className = theme === "dark" ? "ri-sun-line" : "ri-moon-line";
    t2.addEventListener("click", ()=>{
        setTimeout(()=>{
            const nt = document.documentElement.getAttribute("data-theme");
            t2.querySelector("i").className = nt === "dark" ? "ri-sun-line" : "ri-moon-line";
        }, 50);
    });
}
</script>';
}
