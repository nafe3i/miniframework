<?php
/**
 * @var array<string, string> $old
 * @var array<string, list<string>> $errors
 * @var list<App\Entities\TaskStatus> $statuses
 */
$this->layout('layouts/app', ['title' => 'New task']);
?>
<p class="breadcrumb"><a href="/tasks">← All tasks</a></p>
<h1>New task</h1>

<?= $this->insert('tasks/_form', [
    'action' => '/tasks',
    'submitLabel' => 'Create task',
    'old' => $old,
    'errors' => $errors,
    'statuses' => $statuses,
]) ?>
