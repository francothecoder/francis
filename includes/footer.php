    </div>
</main>
<footer class="site-footer mt-4">
    <div class="container footer-shell">
        <div>
            <div class="footer-brand">Francis Kwesa</div>
            <div class="footer-copy">Helping university students win academically, collaborate boldly, and build practical skills.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-md-end">
            <a href="<?= url('community/index.php') ?>" class="btn btn-soft btn-sm">Community</a>
            <a href="<?= url('membership.php') ?>" class="btn btn-soft btn-sm">Membership</a>
            <a href="https://wa.me/<?= e(get_setting('site_whatsapp', '260963884318')) ?>" class="btn btn-success btn-pill btn-sm" target="_blank">WhatsApp</a>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
