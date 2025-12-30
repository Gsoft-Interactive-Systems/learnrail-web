<?php
$courseId = $course['id'] ?? 0;
$isEdit = !empty($lesson);
$action = $isEdit
    ? "/admin/courses/{$courseId}/lessons/{$lesson['id']}/edit"
    : "/admin/courses/{$courseId}/lessons/create";
$title = $isEdit ? 'Edit Lesson' : 'Add Lesson';
$selectedModuleId = $lesson['module_id'] ?? $_GET['module_id'] ?? '';
?>

<div class="d-flex justify-between items-center mb-6">
    <div>
        <a href="/admin/courses/<?= e($courseId) ?>/lessons" class="btn btn-ghost btn-sm mb-2">
            <i class="iconoir-arrow-left"></i>
            Back to Lessons
        </a>
        <h1 class="text-2xl font-bold"><?= $title ?></h1>
        <p class="text-secondary"><?= e($course['title'] ?? 'Course') ?></p>
    </div>
</div>

<form action="<?= $action ?>" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Main Content -->
        <div>
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Lesson Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label class="form-label" for="title">Lesson Title</label>
                        <input type="text" id="title" name="title" class="form-input"
                               value="<?= e($lesson['title'] ?? '') ?>" required
                               placeholder="e.g., Introduction to Variables">
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label" for="description">Description (optional)</label>
                        <textarea id="description" name="description" class="form-textarea" rows="3"
                                  placeholder="Brief description of what this lesson covers"><?= e($lesson['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Lesson Type</label>
                        <div class="d-flex gap-4">
                            <label class="d-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="video"
                                       <?= ($lesson['type'] ?? 'video') === 'video' ? 'checked' : '' ?>
                                       onchange="toggleLessonType('video')">
                                <i class="iconoir-play"></i>
                                <span>Video Lesson</span>
                            </label>
                            <label class="d-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="text"
                                       <?= ($lesson['type'] ?? '') === 'text' ? 'checked' : '' ?>
                                       onchange="toggleLessonType('text')">
                                <i class="iconoir-page"></i>
                                <span>Text Lesson</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Content Section -->
            <div class="card mb-6" id="video-section" style="<?= ($lesson['type'] ?? 'video') !== 'video' ? 'display: none;' : '' ?>">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="iconoir-play"></i>
                        Video Content
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="iconoir-info-circle"></i>
                        <div>
                            <strong>Using Bunny Stream for Videos</strong>
                            <p class="mb-0">Upload your videos to <a href="https://dash.bunny.net" target="_blank">Bunny.net Stream</a>, then paste the video library ID and video ID below. Videos will be streamed securely and cannot be downloaded.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-group mb-0">
                            <label class="form-label" for="bunny_library_id">Bunny Library ID</label>
                            <input type="text" id="bunny_library_id" name="bunny_library_id" class="form-input"
                                   value="<?= e($lesson['bunny_library_id'] ?? '') ?>"
                                   placeholder="e.g., 123456">
                            <p class="text-sm text-secondary mt-1">Found in your Bunny Stream library settings</p>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="bunny_video_id">Bunny Video ID</label>
                            <input type="text" id="bunny_video_id" name="bunny_video_id" class="form-input"
                                   value="<?= e($lesson['bunny_video_id'] ?? '') ?>"
                                   placeholder="e.g., abc123-def456-ghi789">
                            <p class="text-sm text-secondary mt-1">The unique video GUID from Bunny</p>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label" for="video_url">Or Direct Video URL (alternative)</label>
                        <input type="url" id="video_url" name="video_url" class="form-input"
                               value="<?= e($lesson['video_url'] ?? '') ?>"
                               placeholder="https://iframe.mediadelivery.net/embed/123456/video-id">
                        <p class="text-sm text-secondary mt-1">If you have a direct embed URL, paste it here instead</p>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label" for="video_duration">Video Duration (seconds)</label>
                        <input type="number" id="video_duration" name="video_duration" class="form-input"
                               value="<?= e($lesson['video_duration'] ?? '') ?>"
                               placeholder="e.g., 600 for 10 minutes" min="0">
                        <p class="text-sm text-secondary mt-1">Duration in seconds (helps with progress tracking)</p>
                    </div>
                </div>
            </div>

            <!-- Text Content Section -->
            <div class="card mb-6" id="text-section" style="<?= ($lesson['type'] ?? 'video') !== 'text' ? 'display: none;' : '' ?>">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="iconoir-page"></i>
                        Text Content
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label class="form-label" for="content">Lesson Content</label>
                        <textarea id="content" name="content" class="form-textarea" rows="15"
                                  placeholder="Write your lesson content here. You can use markdown formatting."><?= e($lesson['content'] ?? '') ?></textarea>
                        <p class="text-sm text-secondary mt-2">
                            <i class="iconoir-info-circle"></i>
                            Supports Markdown formatting (headers, bold, lists, code blocks, etc.)
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Publish Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Publish</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label class="form-label">Module</label>
                        <select name="module_id" class="form-select" required>
                            <option value="">Select Module</option>
                            <?php foreach ($modules ?? [] as $module): ?>
                                <option value="<?= e($module['id']) ?>"
                                        <?= $selectedModuleId == $module['id'] ? 'selected' : '' ?>>
                                    <?= e($module['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($modules)): ?>
                            <p class="text-sm text-warning mt-2">
                                <i class="iconoir-warning-triangle"></i>
                                No modules yet. <a href="/admin/courses/<?= e($courseId) ?>/lessons">Create one first</a>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label">Status</label>
                        <select name="is_published" class="form-select">
                            <option value="1" <?= ($lesson['is_published'] ?? 1) ? 'selected' : '' ?>>Published</option>
                            <option value="0" <?= isset($lesson['is_published']) && !$lesson['is_published'] ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label class="d-flex items-center gap-3">
                            <input type="checkbox" name="is_free_preview" value="1"
                                   <?= ($lesson['is_free_preview'] ?? false) ? 'checked' : '' ?>
                                   style="width: 18px; height: 18px;">
                            <span>Free Preview</span>
                        </label>
                        <p class="text-sm text-secondary mt-1">Allow non-enrolled users to view this lesson</p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="iconoir-check"></i>
                        <?= $isEdit ? 'Update Lesson' : 'Create Lesson' ?>
                    </button>
                </div>
            </div>

            <!-- Sort Order -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Display Order</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label class="form-label" for="sort_order">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-input"
                               value="<?= e($lesson['sort_order'] ?? 0) ?>" min="0">
                        <p class="text-sm text-secondary mt-1">Lower numbers appear first</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.alert {
    padding: 16px;
    border-radius: var(--radius);
    display: flex;
    gap: 12px;
}
.alert-info {
    background: #EEF2FF;
    color: #4338CA;
}
.alert-info a {
    color: #4338CA;
    text-decoration: underline;
}
.alert i {
    font-size: 20px;
    flex-shrink: 0;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<script>
function toggleLessonType(type) {
    document.getElementById('video-section').style.display = type === 'video' ? 'block' : 'none';
    document.getElementById('text-section').style.display = type === 'text' ? 'block' : 'none';
}
</script>
