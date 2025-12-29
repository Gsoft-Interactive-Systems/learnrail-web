<div class="grid" style="grid-template-columns: 1fr 350px; gap: 24px;">
    <!-- Main Content -->
    <div>
        <div class="card mb-6">
            <div class="card-body text-center py-6">
                <div class="avatar avatar-xl mb-4" style="margin: 0 auto; background: var(--gradient);">
                    <i class="iconoir-suitcase" style="font-size: 2rem;"></i>
                </div>
                <h2 class="text-xl font-bold mb-2">AI Career Assistant</h2>
                <p class="text-secondary mb-4">Get personalized career guidance and course recommendations based on your goals.</p>
            </div>
        </div>

        <!-- Career Assessment -->
        <div class="card" x-data="{ step: 1, answers: {} }">
            <div class="card-header">
                <h3 class="card-title">Career Assessment</h3>
                <span class="badge badge-primary" x-text="'Step ' + step + ' of 4'"></span>
            </div>
            <div class="card-body">
                <!-- Step 1: Current Role -->
                <div x-show="step === 1">
                    <h4 class="font-semibold mb-4">What's your current role?</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <?php
                        $roles = ['Student', 'Entry-level Professional', 'Mid-level Professional', 'Senior Professional', 'Manager', 'Career Changer'];
                        foreach ($roles as $role):
                        ?>
                            <button class="btn btn-outline"
                                    @click="answers.current_role = '<?= e($role) ?>'; step = 2"
                                    :class="{ 'btn-primary': answers.current_role === '<?= e($role) ?>' }">
                                <?= e($role) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 2: Industry Interest -->
                <div x-show="step === 2">
                    <h4 class="font-semibold mb-4">Which industry interests you most?</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <?php
                        $industries = ['Technology', 'Finance', 'Healthcare', 'Education', 'Marketing', 'Design', 'Data Science', 'Entrepreneurship'];
                        foreach ($industries as $industry):
                        ?>
                            <button class="btn btn-outline"
                                    @click="answers.industry = '<?= e($industry) ?>'; step = 3"
                                    :class="{ 'btn-primary': answers.industry === '<?= e($industry) ?>' }">
                                <?= e($industry) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 3: Goals -->
                <div x-show="step === 3">
                    <h4 class="font-semibold mb-4">What's your primary career goal?</h4>
                    <div class="grid grid-cols-1 gap-3">
                        <?php
                        $goals = [
                            'Get my first job in tech',
                            'Get a promotion',
                            'Switch careers completely',
                            'Start my own business',
                            'Develop new skills for my current role'
                        ];
                        foreach ($goals as $goal):
                        ?>
                            <button class="btn btn-outline text-left"
                                    @click="answers.goal = '<?= e($goal) ?>'; step = 4">
                                <?= e($goal) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 4: Time Commitment -->
                <div x-show="step === 4">
                    <h4 class="font-semibold mb-4">How much time can you dedicate to learning weekly?</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <?php
                        $times = ['1-3 hours', '4-7 hours', '8-15 hours', '15+ hours'];
                        foreach ($times as $time):
                        ?>
                            <button class="btn btn-outline"
                                    @click="answers.time = '<?= e($time) ?>'; submitAssessment(answers)">
                                <?= e($time) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="d-flex justify-between mt-6" x-show="step > 1">
                    <button class="btn btn-ghost" @click="step--">
                        <i class="iconoir-arrow-left"></i>
                        Back
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <div class="card" style="position: sticky; top: 88px;">
            <div class="card-header">
                <h3 class="card-title">Why Career Planning?</h3>
            </div>
            <div class="card-body">
                <ul style="list-style: none;">
                    <li class="d-flex gap-3 mb-4">
                        <i class="iconoir-target text-primary"></i>
                        <div>
                            <div class="font-medium">Set Clear Goals</div>
                            <div class="text-sm text-secondary">Define your career path</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-4">
                        <i class="iconoir-book text-success"></i>
                        <div>
                            <div class="font-medium">Get Recommendations</div>
                            <div class="text-sm text-secondary">Courses tailored to your goals</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3">
                        <i class="iconoir-graph-up text-warning"></i>
                        <div>
                            <div class="font-medium">Track Progress</div>
                            <div class="text-sm text-secondary">Monitor your growth</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
async function submitAssessment(answers) {
    try {
        const response = await API.post('/career/assess', answers);
        if (response.success) {
            Toast.success('Assessment complete! Loading your recommendations...');
            setTimeout(() => {
                window.location.href = '/career/recommendations';
            }, 1500);
        }
    } catch (error) {
        Toast.error('Failed to submit assessment. Please try again.');
    }
}
</script>
