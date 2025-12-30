<?php
$courseId = $course['id'] ?? 0;
$modules = $modules ?? [];
?>

<div class="d-flex justify-between items-center mb-6">
    <div>
        <a href="/admin/courses/<?= e($courseId) ?>/edit" class="btn btn-ghost btn-sm mb-2">
            <i class="iconoir-arrow-left"></i>
            Back to Course
        </a>
        <h1 class="text-2xl font-bold">Manage Lessons</h1>
        <p class="text-secondary"><?= e($course['title'] ?? 'Course') ?></p>
    </div>
    <div class="d-flex gap-3">
        <button type="button" class="btn btn-secondary" onclick="showModuleModal()">
            <i class="iconoir-folder-plus"></i>
            Add Module
        </button>
        <a href="/admin/courses/<?= e($courseId) ?>/lessons/create" class="btn btn-primary">
            <i class="iconoir-plus"></i>
            Add Lesson
        </a>
    </div>
</div>

<?php if (empty($modules)): ?>
    <div class="card">
        <div class="card-body text-center py-12">
            <div class="mb-4">
                <i class="iconoir-folder" style="font-size: 48px; color: var(--gray-400);"></i>
            </div>
            <h3 class="font-semibold mb-2">No Modules Yet</h3>
            <p class="text-secondary mb-4">Create a module first to organize your lessons.</p>
            <button type="button" class="btn btn-primary" onclick="showModuleModal()">
                <i class="iconoir-folder-plus"></i>
                Create First Module
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="modules-list" id="modules-list">
        <?php foreach ($modules as $moduleIndex => $module): ?>
            <div class="card mb-4 module-card" data-module-id="<?= e($module['id']) ?>">
                <div class="card-header d-flex justify-between items-center">
                    <div class="d-flex gap-3 items-center">
                        <span class="drag-handle cursor-move text-secondary">
                            <i class="iconoir-drag-hand-gesture"></i>
                        </span>
                        <div>
                            <h3 class="card-title mb-0"><?= e($module['title']) ?></h3>
                            <span class="text-sm text-secondary"><?= count($module['lessons'] ?? []) ?> lessons</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="editModule(<?= e($module['id']) ?>, '<?= e(addslashes($module['title'])) ?>')">
                            <i class="iconoir-edit"></i>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm text-danger" onclick="deleteModule(<?= e($module['id']) ?>)">
                            <i class="iconoir-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($module['lessons'])): ?>
                        <div class="lessons-list" data-module-id="<?= e($module['id']) ?>">
                            <?php foreach ($module['lessons'] as $lessonIndex => $lesson): ?>
                                <div class="lesson-item d-flex items-center gap-4 p-4" data-lesson-id="<?= e($lesson['id']) ?>" style="border-bottom: 1px solid var(--gray-100);">
                                    <span class="drag-handle cursor-move text-secondary">
                                        <i class="iconoir-drag-hand-gesture"></i>
                                    </span>
                                    <span class="badge badge-secondary"><?= $lessonIndex + 1 ?></span>
                                    <div class="flex-1">
                                        <div class="font-medium"><?= e($lesson['title']) ?></div>
                                        <div class="text-sm text-secondary d-flex gap-3">
                                            <?php if ($lesson['type'] === 'video'): ?>
                                                <span><i class="iconoir-play"></i> Video</span>
                                                <?php if ($lesson['video_duration']): ?>
                                                    <span><?= floor($lesson['video_duration'] / 60) ?>:<?= str_pad($lesson['video_duration'] % 60, 2, '0', STR_PAD_LEFT) ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span><i class="iconoir-page"></i> Text</span>
                                            <?php endif; ?>
                                            <?php if ($lesson['is_free_preview']): ?>
                                                <span class="badge badge-success">Free Preview</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="/admin/courses/<?= e($courseId) ?>/lessons/<?= e($lesson['id']) ?>/edit" class="btn btn-ghost btn-sm">
                                            <i class="iconoir-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-ghost btn-sm text-danger" onclick="deleteLesson(<?= e($lesson['id']) ?>)">
                                            <i class="iconoir-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center text-secondary">
                            No lessons in this module yet.
                            <a href="/admin/courses/<?= e($courseId) ?>/lessons/create?module_id=<?= e($module['id']) ?>">Add one</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Module Modal -->
<div id="module-modal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="closeModuleModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="module-modal-title">Add Module</h3>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeModuleModal()">
                <i class="iconoir-xmark"></i>
            </button>
        </div>
        <form id="module-form" action="/admin/courses/<?= e($courseId) ?>/modules" method="POST">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="module_id" id="module-id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Module Title</label>
                    <input type="text" name="title" id="module-title" class="form-input" placeholder="e.g., Getting Started" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description" id="module-description" class="form-textarea" rows="3" placeholder="Brief description of this module"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModuleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Module</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}
.modal-content {
    position: relative;
    background: white;
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 500px;
    margin: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
}
.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    margin: 0;
    font-size: 18px;
}
.modal-body {
    padding: 20px;
}
.modal-footer {
    padding: 20px;
    border-top: 1px solid var(--gray-100);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
.cursor-move {
    cursor: move;
}
.lesson-item:hover {
    background: var(--gray-50);
}
</style>

<script>
function showModuleModal() {
    document.getElementById('module-modal').style.display = 'flex';
    document.getElementById('module-modal-title').textContent = 'Add Module';
    document.getElementById('module-id').value = '';
    document.getElementById('module-title').value = '';
    document.getElementById('module-description').value = '';
    document.getElementById('module-form').action = '/admin/courses/<?= e($courseId) ?>/modules';
}

function editModule(id, title) {
    document.getElementById('module-modal').style.display = 'flex';
    document.getElementById('module-modal-title').textContent = 'Edit Module';
    document.getElementById('module-id').value = id;
    document.getElementById('module-title').value = title;
    document.getElementById('module-form').action = '/admin/courses/<?= e($courseId) ?>/modules/' + id;
}

function closeModuleModal() {
    document.getElementById('module-modal').style.display = 'none';
}

function deleteModule(id) {
    if (confirm('Are you sure you want to delete this module? All lessons in this module will also be deleted.')) {
        fetch('/admin/courses/<?= e($courseId) ?>/modules/' + id + '/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= csrf_token() ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to delete module');
            }
        });
    }
}

function deleteLesson(id) {
    if (confirm('Are you sure you want to delete this lesson?')) {
        fetch('/admin/courses/<?= e($courseId) ?>/lessons/' + id + '/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= csrf_token() ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to delete lesson');
            }
        });
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModuleModal();
    }
});
</script>
