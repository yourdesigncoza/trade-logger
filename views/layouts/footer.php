            <footer class="footer position-absolute">
                <div class="row g-0 justify-content-between align-items-center h-100">
                    <div class="col-12 col-sm-auto text-center">
                        <p class="mb-0 mt-2 mt-sm-0 text-body">
                            Trade Logger <span class="d-none d-sm-inline-block">| </span>
                            <br class="d-sm-none">
                            2025 &copy; <a class="mx-1" href="#">YourDesign.co.za</a>
                        </p>
                    </div>
                    <div class="col-12 col-sm-auto text-center">
                        <p class="mb-0 text-body-tertiary text-opacity-85">v1.22.0</p>
                    </div>
                </div>
            </footer>
        </div>
    </main>

    <!-- Phoenix JavaScript -->
    <script src="<?= BASE_URL ?>/assets/js/vendor/popper.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/bootstrap.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/anchor.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/is.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/fontawesome/all.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/lodash.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/list.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/feather.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/dayjs.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/phoenix.js"></script>
    
    <!-- Chart.js -->
    <script src="<?= BASE_URL ?>/assets/js/vendor/chart.min.js"></script>
    
    <!-- jQuery -->
    <script src="<?= BASE_URL ?>/assets/js/vendor/jquery.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?= BASE_URL . $js_file ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
        // Initialize Phoenix
        phoenixOffcanvas.init();
        phoenixTooltip.init();
    </script>
</body>
</html>