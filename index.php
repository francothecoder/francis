<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';
$stats = [
    'projects' => (int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'resources' => (int)$pdo->query('SELECT COUNT(*) FROM resources')->fetchColumn(),
    'students' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
];
$featuredTopics = $pdo->query("SELECT ct.*, u.name FROM community_topics ct INNER JOIN users u ON u.id = ct.user_id ORDER BY ct.id DESC LIMIT 3")->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero mb-5">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <div class="badge bg-light text-primary mb-3">Student-focused academic support platform</div>
            <h1 class="display-5 fw-bold">Helping university students succeed in their academic journey</h1>
            <p class="lead mt-3">Access projects, coding help, practical IT guidance, resources, subscriptions, and a student community designed to make learning easier, faster, and more effective.</p>
            <div class="d-flex gap-2 flex-wrap mt-4">
                <a href="<?= url('auth/register.php') ?>" class="btn btn-light btn-lg">Join Student Hub</a>
                <a href="<?= url('membership.php') ?>" class="btn btn-outline-light btn-lg">View Membership</a>
                <a href="<?= url('community/index.php') ?>" class="btn btn-outline-light btn-lg">Explore Community</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-soft p-4 text-dark">
                <h4 class="fw-bold">Main Services</h4>
                <ul class="list-clean mb-3">
                    <li>Assignment and project support</li>
                    <li>Ready-made projects and systems</li>
                    <li>Practical IT training and guidance</li>
                </ul>
                <div class="row g-3 text-center">
                    <div class="col-4"><div class="bg-light rounded-4 p-3"><div class="fw-bold fs-4"><?= e($stats['projects']) ?>+</div><small>Projects</small></div></div>
                    <div class="col-4"><div class="bg-light rounded-4 p-3"><div class="fw-bold fs-4"><?= e($stats['resources']) ?>+</div><small>Resources</small></div></div>
                    <div class="col-4"><div class="bg-light rounded-4 p-3"><div class="fw-bold fs-4"><?= e($stats['students']) ?>+</div><small>Students</small></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-5">
    <h2 class="section-title mb-4">Why choose us</h2>
    <div class="row g-4">
        <div class="col-md-4"><div class="card card-soft h-100 p-4"><div class="icon-box mb-3">1</div><h5>Built for Students</h5><p>Everything on the platform is designed around real university challenges, deadlines, project pressure, and practical success.</p></div></div>
        <div class="col-md-4"><div class="card card-soft h-100 p-4"><div class="icon-box mb-3">2</div><h5>Fast, Reliable Support</h5><p>Get practical help, clear direction, and resources you can actually use without wasting time.</p></div></div>
        <div class="col-md-4"><div class="card card-soft h-100 p-4"><div class="icon-box mb-3">3</div><h5>Community + Resources</h5><p>Learn with others, ask questions, share tips, and access materials that support your growth beyond one assignment.</p></div></div>
    </div>
</section>

<section class="mb-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card card-soft p-4 h-100">
                <h3>Mission</h3>
                <p>To empower university students by providing practical resources, reliable support, community interaction, and real-world solutions that make learning easier, faster, and more effective.</p>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-soft p-4 h-100">
                <h3>Vision</h3>
                <p>To become a leading student support platform in Zambia and beyond, where every student can access tools, knowledge, guidance, and community needed to succeed academically and build a strong future in technology.</p>
            </div>
        </div>
    </div>
</section>

<section class="mb-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft p-4 p-lg-5">
                <h2 class="fw-bold">What students can do on the platform</h2>
                <div class="row g-3 mt-2">
                    <div class="col-md-6"><div class="bg-light rounded-4 p-3 h-100">Browse downloadable projects by access level</div></div>
                    <div class="col-md-6"><div class="bg-light rounded-4 p-3 h-100">Subscribe to Basic, Pro, or Elite plans</div></div>
                    <div class="col-md-6"><div class="bg-light rounded-4 p-3 h-100">Read and download premium resources</div></div>
                    <div class="col-md-6"><div class="bg-light rounded-4 p-3 h-100">Post questions and reply inside the community</div></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-soft p-4 h-100">
                <h4>Latest community topics</h4>
                <ul class="list-clean mt-2">
                    <?php foreach ($featuredTopics as $topic): ?>
                        <li>
                            <a class="text-decoration-none fw-semibold" href="<?= url('community/topic.php?id=' . (int)$topic['id']) ?>"><?= e($topic['title']) ?></a>
                            <div class="small-muted"><?= e($topic['category']) ?> · by <?= e($topic['name']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="btn btn-outline-primary mt-3" href="<?= url('community/index.php') ?>">Open Community</a>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="card card-soft p-4 p-lg-5 text-center">
        <h2 class="fw-bold">Ready to join the Student Hub?</h2>
        <p class="mb-4">Create an account, explore free resources, join the community, and subscribe when you are ready for premium support.</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a class="btn btn-primary" href="<?= url('auth/register.php') ?>">Create Account</a>
            <a class="btn btn-success" href="https://wa.me/<?= e(get_setting('site_whatsapp', '260963884318')) ?>" target="_blank">Chat on WhatsApp</a>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
