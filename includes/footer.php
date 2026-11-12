<?php if (!empty($layoutSidebar)): ?>
    </div> <!-- End .container -->
    </main>
<?php else: ?>
    </div> <!-- End .container -->
<?php endif; ?>

<script>
(function () {
    var btn = document.getElementById('sidebarToggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('sidebar', collapsed ? 'collapsed' : 'expanded'); } catch (e) {}
    });
})();
</script>
</body>
</html>
