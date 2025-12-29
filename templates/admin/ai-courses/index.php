<!-- Header -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <p class="text-secondary">Manage AI-powered courses with automated teaching</p>
    </div>
    <a href="/admin/ai-courses/create" class="btn btn-primary">
        <i class="iconoir-plus"></i>
        Create AI Course
    </a>
</div>

<!-- Info Banner -->
<div class="card mb-6" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(16, 185, 129, 0.1)); border: 1px solid var(--primary);">
    <div class="card-body d-flex items-center gap-4">
        <div class="avatar avatar-lg" style="background: var(--gradient);">
            <i class="iconoir-brain" style="font-size: 1.5rem;"></i>
        </div>
        <div>
            <h4 class="font-semibold mb-1">How AI Courses Work</h4>
            <p class="text-secondary text-sm mb-0">
                Create course outlines with modules and lessons. The AI tutor will teach each lesson interactively,
                guiding students through the content and answering their questions in real-time.
            </p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form class="d-flex gap-4 items-end" method="GET">
            <div class="form-group mb-0" style="flex: 1;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-input" placeholder="Course title..." value="<?= e($_GET['search'] ?? '') ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="published" <?= ($_GET['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Level</label>
                <select name="level" class="form-select">
                    <option value="">All Levels</option>
                    <option value="beginner" <?= ($_GET['level'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= ($_GET['level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="advanced" <?= ($_GET['level'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="iconoir-search"></i>
                Filter
            </button>
        </form>
    </div>
</div>

<!-- AI Courses Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Modules</th>
                    <th>Lessons</th>
                    <th>Enrollments</th>
                    <th>Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td>
                                <div class="d-flex items-center gap-3">
                                    <div class="avatar" style="background: var(--gradient);">
                                        <i class="iconoir-brain"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium"><?= e($course['title'] ?? '') ?></div>
                                        <div class="text-sm text-secondary"><?= e(substr($course['description'] ?? '', 0, 50)) ?>...</div>
                                    </div>
                                </div>
                            </td>
                            <td><?= $course['module_count'] ?? 0 ?></td>
                            <td><?= $course['lesson_count'] ?? 0 ?></td>
                            <td><?= number_format($course['enrollment_count'] ?? 0) ?></td>
                            <td>
                                <span class="badge badge-secondary"><?= ucfirst($course['level'] ?? 'beginner') ?></span>
                            </td>
                            <td>
                                <span class="badge <?= !empty($course['is_published']) ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= !empty($course['is_published']) ? 'Published' : 'Draft' ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="/admin/ai-courses/<?= e($course['id']) ?>/edit" class="btn btn-ghost btn-sm" title="Edit">
                                        <i class="iconoir-edit"></i>
                                    </a>
                                    <a href="/admin/ai-courses/<?= e($course['id']) ?>/curriculum" class="btn btn-ghost btn-sm" title="Manage Curriculum">
                                        <i class="iconoir-book"></i>
                                    </a>
                                    <button class="btn btn-ghost btn-sm text-danger" onclick="confirmDelete(<?= e($course['id']) ?>)" title="Delete">
                                        <i class="iconoir-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-8">
                            <div class="text-secondary mb-4">No AI courses created yet.</div>
                            <a href="/admin/ai-courses/create" class="btn btn-primary">
                                <i class="iconoir-plus"></i>
                                Create Your First AI Course
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (($totalPages ?? 1) > 1): ?>
        <div class="card-footer d-flex justify-between items-center">
            <span class="text-secondary">
                Showing <?= (($currentPage ?? 1) - 1) * 20 + 1 ?> to <?= min(($currentPage ?? 1) * 20, $totalCourses ?? 0) ?> of <?= $totalCourses ?? 0 ?> courses
            </span>
            <div class="d-flex gap-2">
                <?php if (($currentPage ?? 1) > 1): ?>
                    <a href="?page=<?= ($currentPage ?? 1) - 1 ?>" class="btn btn-ghost btn-sm">Previous</a>
                <?php endif; ?>
                <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                    <a href="?page=<?= ($currentPage ?? 1) + 1 ?>" class="btn btn-ghost btn-sm">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this AI course? All modules and lessons will be deleted. This cannot be undone.')) {
        deleteCourse(id);
    }
}

async function deleteCourse(id) {
    try {
        // Use POST endpoint for better compatibility
        const response = await API.post('/admin/ai-courses/' + id + '/delete');
        if (response.success) {
            Toast.success('AI course deleted successfully');
            location.reload();
        } else {
            Toast.error(response.message || 'Failed to delete course');
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to delete course');
    }
}
</script>

<style>
.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--gray-100);
}

.table th {
    font-weight: 600;
    color: var(--gray-500);
    font-size: 13px;
    text-transform: uppercase;
    background: var(--gray-50);
}

.table tbody tr:hover {
    background: var(--gray-50);
}

.card-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--gray-100);
}
</style>
