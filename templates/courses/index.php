<!-- Search & Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="d-flex gap-4 flex-wrap">
            <div class="flex-1" style="min-width: 200px;">
                <input type="text" name="search" class="form-input" placeholder="Search courses..." value="<?= e($search ?? '') ?>">
            </div>
            <select name="category" class="form-select" style="width: auto; min-width: 150px;">
                <option value="">All Categories</option>
                <?php foreach ($categories ?? [] as $cat): ?>
                    <option value="<?= e($cat['slug'] ?? $cat['id']) ?>" <?= ($selectedCategory ?? '') == ($cat['slug'] ?? $cat['id']) ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="level" class="form-select" style="width: auto; min-width: 130px;">
                <option value="">All Levels</option>
                <option value="beginner" <?= ($selectedLevel ?? '') == 'beginner' ? 'selected' : '' ?>>Beginner</option>
                <option value="intermediate" <?= ($selectedLevel ?? '') == 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                <option value="advanced" <?= ($selectedLevel ?? '') == 'advanced' ? 'selected' : '' ?>>Advanced</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="iconoir-search"></i>
                Search
            </button>
        </form>
    </div>
</div>

<!-- Courses Grid -->
<?php if (!empty($courses)): ?>
    <div class="grid grid-cols-3 mb-6">
        <?php foreach ($courses as $course): ?>
            <?php Core\View::component('course-card', ['course' => $course]); ?>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if (!empty($meta) && ($meta['last_page'] ?? 1) > 1): ?>
        <div class="pagination">
            <?php
            $currentPage = $meta['current_page'] ?? 1;
            $lastPage = $meta['last_page'] ?? 1;
            $queryString = http_build_query(array_filter([
                'search' => $search ?? '',
                'category' => $selectedCategory ?? '',
                'level' => $selectedLevel ?? ''
            ]));
            ?>

            <?php if ($currentPage > 1): ?>
                <a href="?page=<?= $currentPage - 1 ?><?= $queryString ? '&' . $queryString : '' ?>" class="pagination-btn">
                    <i class="iconoir-nav-arrow-left"></i>
                </a>
            <?php endif; ?>

            <?php for ($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= $queryString ? '&' . $queryString : '' ?>" class="pagination-btn <?= $i == $currentPage ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($currentPage < $lastPage): ?>
                <a href="?page=<?= $currentPage + 1 ?><?= $queryString ? '&' . $queryString : '' ?>" class="pagination-btn">
                    <i class="iconoir-nav-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="iconoir-book"></i>
        </div>
        <h3 class="empty-state-title">No Courses Found</h3>
        <p class="empty-state-text">
            <?php if ($search ?? ''): ?>
                No courses match your search. Try different keywords.
            <?php else: ?>
                There are no courses available at the moment.
            <?php endif; ?>
        </p>
        <?php if ($search ?? ''): ?>
            <a href="/courses" class="btn btn-primary">Clear Filters</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
