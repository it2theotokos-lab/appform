// AppForm Client Utilities
document.addEventListener('DOMContentLoaded', () => {
    console.log("AppForm UI initialised.");

    // Responsive Mobile Sidebar Toggle
    const btnMobileMenu = document.getElementById('btn-mobile-menu');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    if (btnMobileMenu && sidebar && backdrop) {
        btnMobileMenu.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
            backdrop.style.display = sidebar.classList.contains('sidebar-open') ? 'block' : 'none';
        });

        backdrop.addEventListener('click', () => {
            sidebar.classList.remove('sidebar-open');
            backdrop.style.display = 'none';
        });

        // Close sidebar drawer after navigating/clicking menu item (on mobile)
        const sidebarLinks = sidebar.querySelectorAll('.nav-menu a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('sidebar-open');
                    backdrop.style.display = 'none';
                }
            });
        });
    }
});
