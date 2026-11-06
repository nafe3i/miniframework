<?php
/** @var App\Entities\Task $task */
$this->layout('layouts/app', ['title' => $task->title()]);
?>
<p class="breadcrumb"><a href="/tasks">← All tasks</a></p>

<article class="card task-detail">
    <div class="task-detail__header">
        <h1><?= $this->e($task->title()) ?></h1>
        <span class="badge badge--<?= $this->e(strtolower($task->status()->value)) ?>"><?= $this->e($task->status()->label()) ?></span>
    </div>

    <?php if ($task->description() !== null): ?>
        <p class="task-detail__description"><?= nl2br($this->e($task->description())) ?></p>
    <?php else: ?>
        <p class="muted">No description.</p>
    <?php endif; ?>

    <p class="muted small">Task #<?= $task->id() ?></p>

    <div class="form__actions">
        <a class="button" href="/tasks/<?= $task->id() ?>/edit">Edit</a>
    </div>
</article>
