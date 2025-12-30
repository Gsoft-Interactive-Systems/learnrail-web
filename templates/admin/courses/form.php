<?php
$isEdit = !empty($course);
$action = $isEdit ? "/admin/courses/{$course['id']}/edit" : '/admin/courses/create';
$title = $isEdit ? 'Edit Course' : 'Create Course';
?>

<div class="d-flex justify-between items-center mb-6">
    <div>
        <a href="/admin/courses" class="btn btn-ghost btn-sm mb-2">
            <i class="iconoir-arrow-left"></i>
            Back to Courses
        </a>
        <h1 class="text-2xl font-bold"><?= $title ?></h1>
    </div>
</div>

<form action="<?= $action ?>" method="POST" enctype="multipart/form-data" data-loading>
    <?= csrf_field() ?>

    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Main Content -->
        <div>
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Course Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="title">Course Title</label>
                        <input type="text" id="title" name="title" class="form-input"
                               value="<?= e($course['title'] ?? old('title')) ?>" required
                               placeholder="e.g., Introduction to Python Programming">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-textarea" rows="5"
                                  placeholder="Describe what students will learn..."><?= e($course['description'] ?? old('description')) ?></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label" for="instructor">Instructor</label>
                            <input type="text" id="instructor" name="instructor" class="form-input"
                                   value="<?= e($course['instructor'] ?? old('instructor')) ?>"
                                   placeholder="Instructor name">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="duration">Duration</label>
                            <input type="text" id="duration" name="duration" class="form-input"
                                   value="<?= e($course['duration'] ?? old('duration')) ?>"
                                   placeholder="e.g., 10 hours">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Content Preview -->
            <?php if ($isEdit): ?>
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Course Lessons</h3>
                    <a href="/admin/courses/<?= e($course['id']) ?>/lessons" class="btn btn-primary btn-sm">
                        <i class="iconoir-plus"></i>
                        Manage Lessons
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($course['lessons'])): ?>
                        <?php foreach ($course['lessons'] as $index => $lesson): ?>
                            <div class="d-flex items-center gap-4 p-4" style="border-bottom: 1px solid var(--gray-100);">
                                <span class="badge badge-secondary"><?= $index + 1 ?></span>
                                <div class="flex-1">
                                    <div class="font-medium"><?= e($lesson['title']) ?></div>
                                    <div class="text-sm text-secondary"><?= $lesson['duration'] ?? '0' ?> min</div>
                                </div>
                                <span class="badge <?= ($lesson['type'] ?? 'video') === 'video' ? 'badge-primary' : 'badge-secondary' ?>">
                                    <?= ucfirst($lesson['type'] ?? 'video') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-6 text-center text-secondary">
                            No lessons added yet. <a href="/admin/courses/<?= e($course['id']) ?>/lessons">Add lessons</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- What You'll Learn -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">What Students Will Learn</h3>
                </div>
                <div class="card-body" id="outcomes-container">
                    <div id="outcomes-list">
                        <?php
                        $outcomes = [];
                        if (!empty($course['what_you_learn'])) {
                            $outcomes = is_string($course['what_you_learn']) ? json_decode($course['what_you_learn'], true) : $course['what_you_learn'];
                        }
                        if (empty($outcomes)) $outcomes = [''];
                        foreach ($outcomes as $index => $outcome):
                        ?>
                        <div class="outcome-item d-flex gap-3 mb-3">
                            <input type="text" name="learning_outcomes[]" class="form-input"
                                   value="<?= e($outcome) ?>" placeholder="e.g., Build real-world applications">
                            <button type="button" class="btn btn-ghost text-danger remove-outcome" onclick="removeOutcome(this)">
                                <i class="iconoir-trash"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="addOutcome()">
                        <i class="iconoir-plus"></i>
                        Add Outcome
                    </button>
                    <p class="text-sm text-secondary mt-2">List specific skills or knowledge students will gain from this course</p>
                </div>
            </div>

            <script>
            function addOutcome() {
                const list = document.getElementById('outcomes-list');
                const div = document.createElement('div');
                div.className = 'outcome-item d-flex gap-3 mb-3';
                div.innerHTML = `
                    <input type="text" name="learning_outcomes[]" class="form-input" placeholder="e.g., Build real-world applications">
                    <button type="button" class="btn btn-ghost text-danger remove-outcome" onclick="removeOutcome(this)">
                        <i class="iconoir-trash"></i>
                    </button>
                `;
                list.appendChild(div);
            }

            function removeOutcome(btn) {
                const items = document.querySelectorAll('.outcome-item');
                if (items.length > 1) {
                    btn.closest('.outcome-item').remove();
                }
            }
            </script>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Publish -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Publish</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= empty($course['is_published']) ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= !empty($course['is_published']) ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="iconoir-check"></i>
                        <?= $isEdit ? 'Update Course' : 'Create Course' ?>
                    </button>
                </div>
            </div>

            <!-- Thumbnail -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Thumbnail</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($course['thumbnail'])): ?>
                        <img src="<?= e($course['thumbnail']) ?>" alt="" style="width: 100%; border-radius: var(--radius); margin-bottom: 16px;">
                    <?php endif; ?>
                    <input type="file" name="thumbnail" class="form-input" accept="image/*">
                    <p class="text-sm text-secondary mt-2">Recommended: 800x450px</p>
                </div>
            </div>

            <!-- Category & Level -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Organization</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach (($categories ?? []) as $category): ?>
                                <option value="<?= e($category['id']) ?>"
                                        <?= ($course['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select">
                            <option value="beginner" <?= ($course['level'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                            <option value="intermediate" <?= ($course['level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                            <option value="advanced" <?= ($course['level'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-flex items-center gap-3">
                            <input type="checkbox" name="is_premium" value="1"
                                   <?= (isset($course['is_free']) && !$course['is_free']) ? 'checked' : '' ?>
                                   style="width: 18px; height: 18px;">
                            <span>Premium Content</span>
                        </label>
                        <p class="text-sm text-secondary mt-1">Only subscribers can access this course</p>
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tags</h3>
                </div>
                <div class="card-body">
                    <input type="text" name="tags" class="form-input"
                           value="<?= e($course['tags'] ?? old('tags')) ?>"
                           placeholder="python, programming, beginner">
                    <p class="text-sm text-secondary mt-2">Separate tags with commas</p>
                </div>
            </div>
        </div>
    </div>
</form>
