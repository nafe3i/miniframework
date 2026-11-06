<?php
/** @var list<App\Entities\Task> $tasks */
$this->layout('layouts/app', ['title' => 'Tasks']);
?>
<div class="page-header">
    <h1>Tasks</h1>
</div>

<?php if ($tasks === []): ?>
    <div class="empty card">
        <p>No tasks yet.</p>
    </div>
<?php else: ?>
    <ul class="task-list">
        <?php foreach ($tasks as $task): ?>
            <li class="task card">
                <div class="task__main">
                    <a class="task__title" href="/tasks/<?= $task->id() ?>"><?= $this->e($task->title()) ?></a>
                    <?php if ($task->description() !== null): ?>
                        <p class="task__excerpt"><?= $this->e(mb_strimwidth($task->description(), 0, 120, '…')) ?></p>
                    <?php endif; ?>
                </div>
                <span class="badge badge--<?= $this->e(strtolower($task->status()->value)) ?>"><?= $this->e($task->status()->label()) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
