<!-- Header -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <p class="text-secondary">Manage all courses</p>
    </div>
    <a href="/admin/courses/create" class="btn btn-primary">
        <i class="iconoir-plus"></i>
        Add Course
    </a>
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
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach (($categories ?? []) as $category): ?>
                        <option value="<?= e($category['id']) ?>" <?= ($_GET['category'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
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
            <div class="form-group mb-0">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="published" <?= ($_GET['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="iconoir-search"></i>
                Filter
            </button>
        </form>
    </div>
</div>

<!-- Courses Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Category</th>
                    <th>Level</th>
                    <th>Lessons</th>
                    <th>Enrollments</th>
                    <th>Rating</th>
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
                                    <?php if (!empty($course['thumbnail'])): ?>
                                        <img src="<?= e($course['thumbnail']) ?>" alt="" style="width: 48px; height: 36px; border-radius: 4px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 48px; height: 36px; border-radius: 4px; background: var(--gray-200); display: flex; align-items: center; justify-content: center;">
                                            <i class="iconoir-book text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-medium"><?= e($course['title'] ?? '') ?></div>
                                        <div class="text-sm text-secondary"><?= e($course['instructor'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($course['category_name'] ?? 'Uncategorized') ?></td>
                            <td>
                                <span class="badge badge-secondary"><?= ucfirst($course['level'] ?? 'beginner') ?></span>
                            </td>
                            <td><?= $course['lesson_count'] ?? 0 ?></td>
                            <td><?= number_format($course['enrollment_count'] ?? 0) ?></td>
                            <td>
                                <div class="d-flex items-center gap-1">
                                    <i class="iconoir-star-solid text-warning"></i>
                                    <span><?= number_format($course['rating'] ?? 0, 1) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= ($course['status'] ?? 'draft') === 'published' ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= ucfirst($course['status'] ?? 'draft') ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="/courses/<?= e($course['id']) ?>" class="btn btn-ghost btn-sm" target="_blank" title="Preview">
                                        <i class="iconoir-eye"></i>
                                    </a>
                                    <a href="/admin/courses/<?= e($course['id']) ?>/edit" class="btn btn-ghost btn-sm" title="Edit">
                                        <i class="iconoir-edit"></i>
                                    </a>
                                    <a href="/admin/courses/<?= e($course['id']) ?>/lessons" class="btn btn-ghost btn-sm" title="Manage Lessons">
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
                        <td colspan="8" class="text-center py-8 text-secondary">
                            No courses found.
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
    if (confirm('Are you sure you want to delete this course? This will also delete all lessons and enrollments. This action cannot be undone.')) {
        deleteCourse(id);
    }
}

async function deleteCourse(id) {
    try {
        const response = await API.delete('/admin/courses/' + id);
        if (response.success) {
            Toast.success('Course deleted successfully');
            location.reload();
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
