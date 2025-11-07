        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="text-white"><i class="fas fa-hands-helping"></i> <?php echo t('common.brand'); ?></h5>
                    <p class="text-white-50"><?php echo t('common.footer.description'); ?></p>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white"><?php echo t('common.footer.quick_links'); ?></h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>" class="text-white-50 text-decoration-none"><?php echo t('common.nav.home'); ?></a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>auth/login.php" class="text-white-50 text-decoration-none"><?php echo t('common.nav.login'); ?></a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>auth/register.php" class="text-white-50 text-decoration-none"><?php echo t('common.nav.register'); ?></a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white"><?php echo t('common.footer.contact'); ?></h5>
                    <p class="text-white-50">
                        <i class="fas fa-envelope"></i> <?php echo SITE_EMAIL; ?><br>
                        <i class="fas fa-map-marker-alt"></i> <?php echo t('common.footer.location_text'); ?>
                    </p>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center">
                <p class="text-white-50 mb-1"><?php echo t('common.footer.copyright', ['year' => date('Y')]); ?></p>
                <p class="small text-white-50 mb-0"><?php echo t('common.footer.developed_by'); ?></p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>assets/js/main.js"></script>
</body>
</html>