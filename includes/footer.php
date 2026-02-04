</main>

<!-- Footer -->
<footer class="px-4 lg:px-8 py-6 border-t border-slate-800">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-center md:text-left">
            <p class="text-sm text-gray-400">
                &copy;
                <?= date('Y') ?> <span class="text-primary-400 font-medium">Gudang Gizi</span> - Dapur Makan Gizi Gratis
            </p>
            <p class="text-xs text-gray-500 mt-1">Sistem Manajemen Stok & Inventory</p>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-xs text-gray-500">v1.0.0</span>
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span>Sistem Aktif</span>
            </div>
        </div>
    </div>
</footer>
</div>

<script>
    // Mobile Menu Functions
    function openMobileMenu() {
        document.getElementById('sidebar').classList.remove('-translate-x-full');
        document.getElementById('mobileBackdrop').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        document.getElementById('sidebar').classList.add('-translate-x-full');
        document.getElementById('mobileBackdrop').classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.getElementById('mobileMenuBtn').addEventListener('click', openMobileMenu);

    // Notification Toggle
    function toggleNotifications() {
        const panel = document.getElementById('notificationPanel');
        panel.classList.toggle('hidden');
    }

    // Close notifications when clicking outside
    document.addEventListener('click', function (e) {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown.contains(e.target)) {
            document.getElementById('notificationPanel').classList.add('hidden');
        }
    });

    // Global Search
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        let debounceTimer;
        globalSearch.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const query = this.value.trim();
                if (query.length >= 2) {
                    window.location.href = '/gudang-gizi/modules/master/bahan.php?search=' + encodeURIComponent(query);
                }
            }, 500);
        });
    }

    // Flash message auto-hide
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-10px)';
            setTimeout(() => msg.remove(), 300);
        }, 5000);
    });

    // Set page title based on current page
    function setPageTitle(title, subtitle = '') {
        document.getElementById('pageTitle').textContent = title;
        document.getElementById('pageSubtitle').textContent = subtitle;
    }
</script>
</body>

</html>