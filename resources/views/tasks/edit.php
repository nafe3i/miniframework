<?php
/**
 * @var App\Entities\Task $task
 * @var array<string, string> $old
 * @var array<string, list<string>> $errors
 * @var list<App\Entities\TaskStatus> $statuses
 */
$this->layout('layouts/app', ['title' => 'Edit task']);
?>
<p class="breadcrumb"><a href="/tasks/<?= $task->id() ?>">← Back to task</a></p>
<h1>Edit task #<?= $task->id() ?></h1>

<?= $this->insert('tasks/_form', [
    'action' => '/tasks/' . $task->id() . '/update',
    'submitLabel' => 'Save changes',
    'old' => $old,
    'errors' => $errors,
    'statuses' => $statuses,
]) ?>
