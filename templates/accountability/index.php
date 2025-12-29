<div class="grid" style="grid-template-columns: 1fr 350px; gap: 24px;">
    <!-- Main Content -->
    <div>
        <?php if (!empty($partner)): ?>
            <!-- Partner Card -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Your Accountability Partner</h3>
                    <span class="badge badge-success">Active</span>
                </div>
                <div class="card-body">
                    <div class="d-flex items-center gap-4">
                        <div class="avatar avatar-lg" style="background: var(--gradient);">
                            <?= strtoupper(substr($partner['first_name'] ?? 'P', 0, 1)) ?>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold"><?= e($partner['first_name'] ?? '') ?> <?= e($partner['last_name'] ?? '') ?></h4>
                            <p class="text-sm text-secondary">Partner since <?= format_date($partner['paired_at'] ?? '') ?></p>
                        </div>
                        <a href="/accountability/chat/<?= e($partner['id']) ?>" class="btn btn-primary">
                            <i class="iconoir-chat-bubble"></i>
                            Chat
                        </a>
                    </div>

                    <!-- Partner Stats -->
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="text-center p-4" style="background: var(--gray-50); border-radius: var(--radius);">
                            <div class="text-2xl font-bold text-primary"><?= $partner['streak'] ?? 0 ?></div>
                            <div class="text-sm text-secondary">Day Streak</div>
                        </div>
                        <div class="text-center p-4" style="background: var(--gray-50); border-radius: var(--radius);">
                            <div class="text-2xl font-bold text-success"><?= $partner['courses_completed'] ?? 0 ?></div>
                            <div class="text-sm text-secondary">Courses Done</div>
                        </div>
                        <div class="text-center p-4" style="background: var(--gray-50); border-radius: var(--radius);">
                            <div class="text-2xl font-bold text-warning"><?= $partner['xp'] ?? 0 ?></div>
                            <div class="text-sm text-secondary">XP Earned</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shared Goals -->
            <?php if (!empty($sharedGoals)): ?>
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Shared Goals</h3>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($sharedGoals as $goal): ?>
                        <div class="d-flex items-center gap-4 p-4" style="border-bottom: 1px solid var(--gray-100);">
                            <i class="iconoir-target text-primary"></i>
                            <div class="flex-1">
                                <div class="font-medium"><?= e($goal['title']) ?></div>
                                <div class="progress-bar mt-2" style="height: 6px;">
                                    <div class="progress-bar-fill" style="width: <?= $goal['progress'] ?? 0 ?>%;"></div>
                                </div>
                            </div>
                            <span class="text-sm text-secondary"><?= $goal['progress'] ?? 0 ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Partner's Recent Activity</h3>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($partnerActivity)): ?>
                        <?php foreach ($partnerActivity as $activity): ?>
                            <div class="d-flex items-center gap-4 p-4" style="border-bottom: 1px solid var(--gray-100);">
                                <div class="avatar avatar-sm" style="background: var(--<?= $activity['color'] ?? 'primary' ?>-light);">
                                    <i class="iconoir-<?= $activity['icon'] ?? 'check' ?> text-<?= $activity['color'] ?? 'primary' ?>"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium"><?= e($activity['title']) ?></div>
                                    <div class="text-sm text-secondary"><?= time_ago($activity['created_at'] ?? '') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-6 text-center text-secondary">
                            No recent activity from your partner.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- No Partner - Find One -->
            <div class="card">
                <div class="card-body text-center py-8">
                    <div class="avatar avatar-xl mb-4" style="margin: 0 auto; background: var(--gradient);">
                        <i class="iconoir-group" style="font-size: 2rem;"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">Find an Accountability Partner</h2>
                    <p class="text-secondary mb-6" style="max-width: 400px; margin: 0 auto;">
                        Stay motivated and achieve your goals faster by partnering with someone who shares your learning journey.
                    </p>
                    <button class="btn btn-primary btn-lg" onclick="findPartner()">
                        <i class="iconoir-search"></i>
                        Find a Partner
                    </button>
                </div>
            </div>

            <!-- Benefits -->
            <div class="grid grid-cols-3 gap-4 mt-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="iconoir-rocket text-primary" style="font-size: 2rem;"></i>
                        <h4 class="font-semibold mt-3 mb-2">Stay Motivated</h4>
                        <p class="text-sm text-secondary">Regular check-ins keep you on track</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="iconoir-trophy text-success" style="font-size: 2rem;"></i>
                        <h4 class="font-semibold mt-3 mb-2">Achieve More</h4>
                        <p class="text-sm text-secondary">Partners complete 40% more courses</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="iconoir-community text-warning" style="font-size: 2rem;"></i>
                        <h4 class="font-semibold mt-3 mb-2">Build Connections</h4>
                        <p class="text-sm text-secondary">Network with like-minded learners</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
        <?php if (!empty($partner)): ?>
            <!-- Quick Actions -->
            <div class="card mb-4" style="position: sticky; top: 88px;">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <a href="/accountability/chat/<?= e($partner['id']) ?>" class="btn btn-primary btn-block mb-3">
                        <i class="iconoir-chat-bubble"></i>
                        Send Message
                    </a>
                    <button class="btn btn-outline btn-block mb-3" onclick="Modal.open('nudge-modal')">
                        <i class="iconoir-bell"></i>
                        Send Nudge
                    </button>
                    <button class="btn btn-ghost btn-block text-danger" onclick="confirmUnpair()">
                        <i class="iconoir-user-minus"></i>
                        End Partnership
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- How It Works -->
            <div class="card" style="position: sticky; top: 88px;">
                <div class="card-header">
                    <h3 class="card-title">How It Works</h3>
                </div>
                <div class="card-body">
                    <ul style="list-style: none;">
                        <li class="d-flex gap-3 mb-4">
                            <div class="avatar avatar-sm bg-primary">1</div>
                            <div>
                                <div class="font-medium">Get Matched</div>
                                <div class="text-sm text-secondary">We'll find someone with similar goals</div>
                            </div>
                        </li>
                        <li class="d-flex gap-3 mb-4">
                            <div class="avatar avatar-sm bg-primary">2</div>
                            <div>
                                <div class="font-medium">Set Shared Goals</div>
                                <div class="text-sm text-secondary">Define what you want to achieve together</div>
                            </div>
                        </li>
                        <li class="d-flex gap-3 mb-4">
                            <div class="avatar avatar-sm bg-primary">3</div>
                            <div>
                                <div class="font-medium">Check In Regularly</div>
                                <div class="text-sm text-secondary">Message each other and track progress</div>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <div class="avatar avatar-sm bg-primary">4</div>
                            <div>
                                <div class="font-medium">Achieve Together</div>
                                <div class="text-sm text-secondary">Celebrate wins and support each other</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Nudge Modal -->
<div class="modal-overlay" id="nudge-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Send a Nudge</h3>
            <button class="modal-close" onclick="Modal.close('nudge-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-secondary mb-4">Send a friendly reminder to your partner to keep them motivated!</p>
            <div class="grid grid-cols-2 gap-3">
                <button class="btn btn-outline" onclick="sendNudge('Keep going!')">
                    💪 Keep going!
                </button>
                <button class="btn btn-outline" onclick="sendNudge('Don\\'t forget to study!')">
                    📚 Study reminder
                </button>
                <button class="btn btn-outline" onclick="sendNudge('You\\'ve got this!')">
                    🌟 You've got this!
                </button>
                <button class="btn btn-outline" onclick="sendNudge('Let\\'s learn together!')">
                    🤝 Learn together
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function findPartner() {
    try {
        const response = await API.post('/accountability/find-partner');
        if (response.success) {
            Toast.success('Finding you a partner...');
            setTimeout(() => location.reload(), 2000);
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to find partner');
    }
}

async function sendNudge(message) {
    try {
        const response = await API.post('/accountability/nudge', { message });
        if (response.success) {
            Toast.success('Nudge sent!');
            Modal.close('nudge-modal');
        }
    } catch (error) {
        Toast.error('Failed to send nudge');
    }
}

function confirmUnpair() {
    if (confirm('Are you sure you want to end this partnership? This action cannot be undone.')) {
        unpairPartner();
    }
}

async function unpairPartner() {
    try {
        const response = await API.post('/accountability/unpair');
        if (response.success) {
            Toast.success('Partnership ended');
            setTimeout(() => location.reload(), 1500);
        }
    } catch (error) {
        Toast.error('Failed to end partnership');
    }
}
</script>
