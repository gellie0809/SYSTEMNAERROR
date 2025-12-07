<?php
// Common sidebar include - markup only; styles moved to css/sidebar.css
?>
<div class="sidebar">
    <div class="logo">LSPU<br><span style="font-size:0.9em;font-weight:400;">College of Criminal Justice
            Education</span></div>
    <div class="sidebar-nav">
        <a href="dashboard_ccje.php"
            class="<?php if(basename($_SERVER['PHP_SELF'])==='dashboard_ccje.php') echo 'active'; ?>"
            data-icon-default="fas fa-chart-pie" data-icon-active="fas fa-gauge">
            <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
        </a>
        <a href="add_board_passer_ccje.php"
            class="<?php if(basename($_SERVER['PHP_SELF'])==='add_board_passer_ccje.php') echo 'active'; ?>"
            data-icon-default="fas fa-user-plus" data-icon-active="fas fa-user-check">
            <i class="fas fa-user-plus"></i> <span>Add New Board Examinee</span>
        </a>
        <a href="manage_data_ccje.php"
            class="<?php if(basename($_SERVER['PHP_SELF'])==='manage_data_ccje.php') echo 'active'; ?>"
            data-icon-default="fas fa-book" data-icon-active="fas fa-book-open">
            <i class="fas fa-book"></i> <span>Manage Data</span>
        </a>
        <a href="import_data_ccje.php"
            class="<?php if(basename($_SERVER['PHP_SELF'])==='import_data_ccje.php') echo 'active'; ?>"
            data-icon-default="fas fa-upload" data-icon-active="fas fa-file-arrow-up">
            <i class="fas fa-upload"></i> <span>Import Data</span>
        </a>
        <a href="export_data_ccje.php"
            class="<?php if(basename($_SERVER['PHP_SELF'])==='export_data_ccje.php') echo 'active'; ?>"
            data-icon-default="fas fa-download" data-icon-active="fas fa-file-arrow-down">
            <i class="fas fa-download"></i> <span>Export Data</span>
        </a>
        <a href="view_statistics_ccje.php"
            class="<?php if(basename($_SERVER['PHP_SELF'])==='view_statistics_ccje.php') echo 'active'; ?>"
            data-icon-default="fas fa-chart-bar" data-icon-active="fas fa-chart-line">
            <i class="fas fa-chart-bar"></i> <span>View Statistics</span>
        </a>
        <a href="testing_anonymous_data_ccje.php"
            class="<?php if(basename($_SERVER['PHP_SELF'])==='testing_anonymous_data_ccje.php' || basename($_SERVER['PHP_SELF'])==='anonymous_dashboard_ccje.php') echo 'active'; ?>"
            data-icon-default="fas fa-flask" data-icon-active="fas fa-vial">
            <i class="fas fa-flask"></i> <span>Testing Anonymous Data</span>
        </a>
        <a href="view_anonymous_statistics_ccje.php"
            class="<?php if(basename($_SERVER['PHP_SELF'])==='view_anonymous_statistics_ccje.php') echo 'active'; ?>"
            data-icon-default="fas fa-chart-pie" data-icon-active="fas fa-chart-area">
            <i class="fas fa-chart-pie"></i> <span>View Anonymous Data Statistics</span>
        </a>
    </div>
    <script>
    (function() {
        try {
            var links = document.querySelectorAll('.sidebar-nav a');

            function applyIconState(el) {
                var icon = el.querySelector('i');
                if (!icon) return;
                var def = el.getAttribute('data-icon-default') || '';
                var act = el.getAttribute('data-icon-active') || def;
                var cls = el.classList.contains('active') ? act : def;
                if (cls) icon.className = cls;
            }
            links.forEach(function(a) {
                applyIconState(a);
                a.addEventListener('click', function() {
                    // optimistic UI: swap icons immediately; navigation will follow for normal links
                    links.forEach(function(b) {
                        b.classList.remove('active');
                        applyIconState(b);
                    });
                    a.classList.add('active');
                    applyIconState(a);
                });
            });
        } catch (e) {
            /* no-op */
        }
    })();
    </script>
</div>