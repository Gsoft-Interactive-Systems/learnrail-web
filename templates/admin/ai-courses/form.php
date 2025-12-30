<?php
$isEdit = !empty($course);
$action = $isEdit ? "/admin/ai-courses/{$course['id']}/update" : '/admin/ai-courses/store';
$title = $isEdit ? 'Edit AI Course' : 'Create AI Course';
?>

<div class="d-flex justify-between items-center mb-6">
    <div>
        <a href="/admin/ai-courses" class="btn btn-ghost btn-sm mb-2">
            <i class="iconoir-arrow-left"></i>
            Back to AI Courses
        </a>
        <h1 class="text-2xl font-bold"><?= $title ?></h1>
    </div>
</div>

<form action="<?= $action ?>" method="POST" data-loading>
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
                               placeholder="e.g., Introduction to Machine Learning">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-textarea" rows="4"
                                  placeholder="Describe what students will learn in this AI-powered course..."><?= e($course['description'] ?? old('description')) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="learning_objectives">Learning Objectives</label>
                        <textarea id="learning_objectives" name="learning_objectives" class="form-textarea" rows="3"
                                  placeholder="What will students be able to do after completing this course?"><?= e($course['learning_objectives'] ?? old('learning_objectives')) ?></textarea>
                        <p class="text-sm text-secondary mt-1">The AI tutor will use these objectives to guide the teaching.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label" for="estimated_duration">Estimated Duration</label>
                            <input type="text" id="estimated_duration" name="estimated_duration" class="form-input"
                                   value="<?= e($course['estimated_duration'] ?? old('estimated_duration')) ?>"
                                   placeholder="e.g., 5 hours">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="level">Difficulty Level</label>
                            <select id="level" name="level" class="form-select">
                                <option value="beginner" <?= ($course['level'] ?? 'beginner') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                                <option value="intermediate" <?= ($course['level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                                <option value="advanced" <?= ($course['level'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Teaching Instructions -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">AI Teaching Instructions</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="ai_instructions">Instructions for AI Tutor</label>
                        <textarea id="ai_instructions" name="ai_instructions" class="form-textarea" rows="5"
                                  placeholder="Provide specific instructions for how the AI should teach this course. Include tone, style, examples to use, etc."><?= e($course['ai_instructions'] ?? old('ai_instructions')) ?></textarea>
                        <p class="text-sm text-secondary mt-1">
                            Example: "Use simple language suitable for beginners. Include real-world examples from everyday life.
                            Ask questions to check understanding after each concept."
                        </p>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label" for="prerequisites">Prerequisites</label>
                        <textarea id="prerequisites" name="prerequisites" class="form-textarea" rows="2"
                                  placeholder="What should students know before starting this course?"><?= e($course['prerequisites'] ?? old('prerequisites')) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Quick Curriculum Setup -->
            <?php if (!$isEdit): ?>
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title"><i class="iconoir-flash text-warning"></i> Quick Curriculum Setup</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="iconoir-info-circle"></i>
                        <div>
                            <strong>Paste Your Curriculum</strong>
                            <p class="mb-0">Paste a structured curriculum below and it will automatically create modules and lessons. Use the format shown in the placeholder.</p>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" for="curriculum_text">Curriculum Content</label>
                        <textarea id="curriculum_text" name="curriculum_text" class="form-textarea" rows="15"
                                  placeholder="Module 1: Introduction to the Topic
- Lesson 1: What is This Topic?
- Lesson 2: History and Background
- Lesson 3: Key Concepts Overview

Module 2: Core Fundamentals
- Lesson 1: Understanding the Basics
- Lesson 2: Common Terminology
- Lesson 3: Practical Applications

Module 3: Advanced Concepts
- Lesson 1: Deep Dive into Advanced Topics
- Lesson 2: Real-World Case Studies
- Lesson 3: Best Practices"></textarea>
                        <p class="text-sm text-secondary mt-2">
                            <strong>Format:</strong> Start each module with "Module X:" and each lesson with "- Lesson X:". The AI tutor will use these as teaching guidelines.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Curriculum Preview (Edit Mode Only) -->
            <?php if ($isEdit): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Course Curriculum</h3>
                    <a href="/admin/ai-courses/<?= e($course['id']) ?>/curriculum" class="btn btn-primary btn-sm">
                        <i class="iconoir-edit"></i>
                        Manage Curriculum
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($course['modules'])): ?>
                        <?php foreach ($course['modules'] as $moduleIndex => $module): ?>
                            <div class="module-item">
                                <div class="module-header">
                                    <span class="badge badge-primary"><?= $moduleIndex + 1 ?></span>
                                    <span class="font-semibold"><?= e($module['title']) ?></span>
                                    <span class="text-sm text-secondary ml-auto"><?= count($module['lessons'] ?? []) ?> lessons</span>
                                </div>
                                <?php if (!empty($module['lessons'])): ?>
                                    <div class="module-lessons">
                                        <?php foreach ($module['lessons'] as $lessonIndex => $lesson): ?>
                                            <div class="lesson-item">
                                                <i class="iconoir-play-solid text-primary"></i>
                                                <span><?= e($lesson['title']) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-6 text-center text-secondary">
                            No modules added yet. <a href="/admin/ai-courses/<?= e($course['id']) ?>/curriculum">Add modules and lessons</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
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
                        <p class="text-sm text-secondary mt-1">Only published courses are visible to students.</p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="iconoir-check"></i>
                        <?= $isEdit ? 'Update Course' : 'Create Course' ?>
                    </button>

                    <?php if ($isEdit): ?>
                        <a href="/admin/ai-courses/<?= e($course['id']) ?>/curriculum" class="btn btn-outline btn-block mt-3">
                            <i class="iconoir-book"></i>
                            Manage Curriculum
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thumbnail -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Course Image</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($course['thumbnail'])): ?>
                        <img src="<?= e($course['thumbnail']) ?>" alt="" style="width: 100%; border-radius: var(--radius); margin-bottom: 16px;">
                    <?php else: ?>
                        <div style="width: 100%; height: 120px; background: var(--gradient); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                            <i class="iconoir-brain" style="font-size: 3rem; color: white;"></i>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="thumbnail" class="form-input" accept="image/*">
                    <p class="text-sm text-secondary mt-2">Recommended: 800x450px</p>
                </div>
            </div>

            <!-- Category -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Category</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
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
                </div>
            </div>

            <!-- Premium -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Access</h3>
                </div>
                <div class="card-body">
                    <label class="d-flex items-center gap-3">
                        <input type="checkbox" name="is_premium" value="1"
                               <?= ($course['is_premium'] ?? false) ? 'checked' : '' ?>
                               style="width: 18px; height: 18px;">
                        <span>Premium Content</span>
                    </label>
                    <p class="text-sm text-secondary mt-2">Only subscribers can access premium AI courses.</p>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.module-item {
    border-bottom: 1px solid var(--gray-100);
}

.module-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--gray-50);
}

.module-lessons {
    padding: 8px 16px 16px 48px;
}

.lesson-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    font-size: 14px;
    color: var(--gray-600);
}
</style>
