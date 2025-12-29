<?php
$courseId = $course['id'] ?? 0;
$modules = $course['modules'] ?? [];
?>

<div class="d-flex justify-between items-center mb-6">
    <div>
        <a href="/admin/ai-courses/<?= e($courseId) ?>/edit" class="btn btn-ghost btn-sm mb-2">
            <i class="iconoir-arrow-left"></i>
            Back to Course
        </a>
        <h1 class="text-2xl font-bold">Curriculum: <?= e($course['title'] ?? 'AI Course') ?></h1>
        <p class="text-secondary">Add modules and lessons for the AI tutor to teach</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('add-module-modal')">
        <i class="iconoir-plus"></i>
        Add Module
    </button>
</div>

<!-- Curriculum Builder -->
<div class="curriculum-builder" x-data="curriculumBuilder(<?= json_encode($modules) ?>)">
    <template x-if="modules.length === 0">
        <div class="card">
            <div class="card-body text-center py-8">
                <div class="avatar avatar-xl mb-4" style="margin: 0 auto; background: var(--gradient);">
                    <i class="iconoir-book" style="font-size: 2rem;"></i>
                </div>
                <h3 class="font-semibold mb-2">No Modules Yet</h3>
                <p class="text-secondary mb-4">Start building your curriculum by adding the first module.</p>
                <button class="btn btn-primary" onclick="Modal.open('add-module-modal')">
                    <i class="iconoir-plus"></i>
                    Add First Module
                </button>
            </div>
        </div>
    </template>

    <template x-for="(module, moduleIndex) in modules" :key="module.id || moduleIndex">
        <div class="card mb-4 module-card">
            <div class="card-header module-header" style="background: var(--gray-50);">
                <div class="d-flex items-center gap-3 flex-1">
                    <div class="drag-handle" title="Drag to reorder">
                        <i class="iconoir-drag-hand-gesture"></i>
                    </div>
                    <span class="badge badge-primary" x-text="'Module ' + (moduleIndex + 1)"></span>
                    <h3 class="card-title mb-0" x-text="module.title"></h3>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-ghost btn-sm" @click="editModule(moduleIndex)" title="Edit Module">
                        <i class="iconoir-edit"></i>
                    </button>
                    <button class="btn btn-ghost btn-sm" @click="addLesson(moduleIndex)" title="Add Lesson">
                        <i class="iconoir-plus"></i>
                    </button>
                    <button class="btn btn-ghost btn-sm text-danger" @click="deleteModule(moduleIndex)" title="Delete Module">
                        <i class="iconoir-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <p class="text-secondary text-sm p-4 mb-0" x-show="module.description" x-text="module.description"></p>

                <!-- Lessons -->
                <div class="lessons-list">
                    <template x-if="!module.lessons || module.lessons.length === 0">
                        <div class="p-4 text-center text-secondary" style="border-top: 1px solid var(--gray-100);">
                            <p class="mb-2">No lessons in this module yet.</p>
                            <button class="btn btn-outline btn-sm" @click="addLesson(moduleIndex)">
                                <i class="iconoir-plus"></i>
                                Add Lesson
                            </button>
                        </div>
                    </template>

                    <template x-for="(lesson, lessonIndex) in module.lessons" :key="lesson.id || lessonIndex">
                        <div class="lesson-item">
                            <div class="drag-handle">
                                <i class="iconoir-drag-hand-gesture"></i>
                            </div>
                            <div class="lesson-number" x-text="(moduleIndex + 1) + '.' + (lessonIndex + 1)"></div>
                            <div class="lesson-info">
                                <div class="font-medium" x-text="lesson.title"></div>
                                <div class="text-sm text-secondary" x-text="lesson.estimated_time || '~10 min'"></div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-ghost btn-sm" @click="editLesson(moduleIndex, lessonIndex)" title="Edit">
                                    <i class="iconoir-edit"></i>
                                </button>
                                <button class="btn btn-ghost btn-sm text-danger" @click="deleteLesson(moduleIndex, lessonIndex)" title="Delete">
                                    <i class="iconoir-trash"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <!-- Save Button -->
    <div class="d-flex justify-end gap-3 mt-6" x-show="modules.length > 0">
        <a href="/admin/ai-courses/<?= e($courseId) ?>/edit" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary" @click="saveCurriculum()" :disabled="saving">
            <i class="iconoir-check"></i>
            <span x-text="saving ? 'Saving...' : 'Save Curriculum'"></span>
        </button>
    </div>
</div>

<!-- Add/Edit Module Modal -->
<div class="modal-overlay" id="add-module-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="module-modal-title">Add Module</h3>
            <button class="modal-close" onclick="Modal.close('add-module-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="module-form">
                <input type="hidden" name="module_index" id="module-index" value="-1">
                <div class="form-group">
                    <label class="form-label">Module Title</label>
                    <input type="text" name="title" id="module-title" class="form-input" placeholder="e.g., Getting Started" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="description" id="module-description" class="form-textarea" rows="3"
                              placeholder="Brief description of what this module covers..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('add-module-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveModule()">Save Module</button>
        </div>
    </div>
</div>

<!-- Add/Edit Lesson Modal -->
<div class="modal-overlay" id="add-lesson-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title" id="lesson-modal-title">Add Lesson</h3>
            <button class="modal-close" onclick="Modal.close('add-lesson-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="lesson-form">
                <input type="hidden" name="module_index" id="lesson-module-index" value="-1">
                <input type="hidden" name="lesson_index" id="lesson-index" value="-1">

                <div class="form-group">
                    <label class="form-label">Lesson Title</label>
                    <input type="text" name="title" id="lesson-title" class="form-input"
                           placeholder="e.g., What is Machine Learning?" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Content Outline</label>
                    <textarea name="content" id="lesson-content" class="form-textarea" rows="5"
                              placeholder="Write the key points, concepts, and information the AI should teach in this lesson. Be specific and thorough."></textarea>
                    <p class="text-sm text-secondary mt-1">The AI tutor will use this content to teach the student interactively.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Estimated Time</label>
                        <input type="text" name="estimated_time" id="lesson-time" class="form-input"
                               placeholder="e.g., 15 min">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Order</label>
                        <input type="number" name="order" id="lesson-order" class="form-input"
                               placeholder="Auto" min="1">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">Learning Objectives (Optional)</label>
                    <textarea name="objectives" id="lesson-objectives" class="form-textarea" rows="2"
                              placeholder="What should students understand after this lesson?"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('add-lesson-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveLesson()">Save Lesson</button>
        </div>
    </div>
</div>

<script>
let curriculumData = null;

function curriculumBuilder(initialModules) {
    return {
        modules: initialModules || [],
        saving: false,

        editModule(index) {
            const module = this.modules[index];
            document.getElementById('module-modal-title').textContent = 'Edit Module';
            document.getElementById('module-index').value = index;
            document.getElementById('module-title').value = module.title || '';
            document.getElementById('module-description').value = module.description || '';
            Modal.open('add-module-modal');
        },

        deleteModule(index) {
            if (confirm('Delete this module and all its lessons?')) {
                this.modules.splice(index, 1);
            }
        },

        addLesson(moduleIndex) {
            document.getElementById('lesson-modal-title').textContent = 'Add Lesson';
            document.getElementById('lesson-module-index').value = moduleIndex;
            document.getElementById('lesson-index').value = -1;
            document.getElementById('lesson-form').reset();
            document.getElementById('lesson-module-index').value = moduleIndex;
            Modal.open('add-lesson-modal');
        },

        editLesson(moduleIndex, lessonIndex) {
            const lesson = this.modules[moduleIndex].lessons[lessonIndex];
            document.getElementById('lesson-modal-title').textContent = 'Edit Lesson';
            document.getElementById('lesson-module-index').value = moduleIndex;
            document.getElementById('lesson-index').value = lessonIndex;
            document.getElementById('lesson-title').value = lesson.title || '';
            document.getElementById('lesson-content').value = lesson.content || '';
            document.getElementById('lesson-time').value = lesson.estimated_time || '';
            document.getElementById('lesson-order').value = lesson.order || '';
            document.getElementById('lesson-objectives').value = lesson.objectives || '';
            Modal.open('add-lesson-modal');
        },

        deleteLesson(moduleIndex, lessonIndex) {
            if (confirm('Delete this lesson?')) {
                this.modules[moduleIndex].lessons.splice(lessonIndex, 1);
            }
        },

        async saveCurriculum() {
            this.saving = true;
            try {
                const response = await API.put('/admin/ai-courses/<?= e($courseId) ?>/curriculum', {
                    modules: this.modules
                });
                if (response.success) {
                    Toast.success('Curriculum saved successfully');
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to save curriculum');
            }
            this.saving = false;
        },

        init() {
            curriculumData = this;
        }
    };
}

function saveModule() {
    const index = parseInt(document.getElementById('module-index').value);
    const title = document.getElementById('module-title').value;
    const description = document.getElementById('module-description').value;

    if (!title.trim()) {
        Toast.error('Module title is required');
        return;
    }

    const moduleData = { title, description, lessons: [] };

    if (index === -1) {
        curriculumData.modules.push(moduleData);
    } else {
        moduleData.lessons = curriculumData.modules[index].lessons || [];
        curriculumData.modules[index] = { ...curriculumData.modules[index], ...moduleData };
    }

    Modal.close('add-module-modal');
    document.getElementById('module-form').reset();
    document.getElementById('module-index').value = -1;
    document.getElementById('module-modal-title').textContent = 'Add Module';
}

function saveLesson() {
    const moduleIndex = parseInt(document.getElementById('lesson-module-index').value);
    const lessonIndex = parseInt(document.getElementById('lesson-index').value);

    const lessonData = {
        title: document.getElementById('lesson-title').value,
        content: document.getElementById('lesson-content').value,
        estimated_time: document.getElementById('lesson-time').value,
        order: document.getElementById('lesson-order').value,
        objectives: document.getElementById('lesson-objectives').value
    };

    if (!lessonData.title.trim()) {
        Toast.error('Lesson title is required');
        return;
    }

    if (!curriculumData.modules[moduleIndex].lessons) {
        curriculumData.modules[moduleIndex].lessons = [];
    }

    if (lessonIndex === -1) {
        curriculumData.modules[moduleIndex].lessons.push(lessonData);
    } else {
        curriculumData.modules[moduleIndex].lessons[lessonIndex] = {
            ...curriculumData.modules[moduleIndex].lessons[lessonIndex],
            ...lessonData
        };
    }

    Modal.close('add-lesson-modal');
    document.getElementById('lesson-form').reset();
}
</script>

<style>
.module-card {
    transition: box-shadow 0.2s;
}

.module-card:hover {
    box-shadow: var(--shadow-lg);
}

.drag-handle {
    cursor: grab;
    color: var(--gray-400);
    padding: 4px;
}

.drag-handle:hover {
    color: var(--gray-600);
}

.lesson-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-top: 1px solid var(--gray-100);
    transition: background 0.2s;
}

.lesson-item:hover {
    background: var(--gray-50);
}

.lesson-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 13px;
    color: var(--gray-600);
}

.lesson-info {
    flex: 1;
}
</style>
